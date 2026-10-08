import 'package:drift/drift.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:uuid/uuid.dart';

import '../../core/models.dart';
import '../../data/database.dart';
import '../sales/sale_domain.dart';
import '../sales/sales_repository.dart';

typedef TicketPrinter = Future<bool> Function(StoredReceipt receipt);

String receiptText(StoredReceipt r) {
  final s = r.sale;
  return [
    'AGROSYS · ${s.branchName}',
    s.localFolio,
    if (s.serverFolio != null) 'Folio central: ${s.serverFolio}',
    saleStatusText(s.status),
    'Fecha: ${DateTime.parse(s.occurredAt).toLocal()}',
    'Atendió: ${s.operatorName}',
    'Cliente: ${s.customerName}',
    '${s.saleType} · Descuento ${decimalText(s.discountBasisPoints)}%',
    '',
    for (final i in r.items)
      '${i.productName}\n${decimalText(i.quantityUnits)} × ${money(i.unitPriceCents)} = ${money(i.totalCents)}',
    '',
    'TOTAL: ${money(s.totalCents)}',
    '${s.saleType == 'Credito' ? 'Inicial' : 'Pago aplicado'}: ${money(s.appliedCents)}',
    'Efectivo recibido: ${money(s.receivedCents)}',
    'Cambio: ${money(s.changeCents)}',
    'Saldo de esta venta: ${money(s.balanceCents)}',
    s.paid ? 'Venta pagada' : 'Saldo pendiente de esta venta',
    if (s.status != 'confirmed')
      'Guardada en este dispositivo; pendiente de confirmación central.',
    'Comprobante local de venta. No es factura fiscal.',
  ].join('\n');
}

Future<Uint8List> receiptPdf(
  StoredReceipt receipt, {
  PdfPageFormat format = PdfPageFormat.a4,
}) async {
  final font = pw.Font.ttf(
    await rootBundle.load('assets/fonts/Lato-Regular.ttf'),
  );
  final doc = pw.Document();
  final lines = receiptText(receipt).split('\n');
  final thermal = format.width <= 90 * PdfPageFormat.mm;
  if (thermal) {
    // Page supports continuous roll height; MultiPage requires a finite height.
    // Honor finite spooler pages too, without clipping long tickets.
    final roll = PdfPageFormat(80 * PdfPageFormat.mm, double.infinity);
    final content = <pw.Widget>[
      pw.Center(
        child: pw.Text(
          'AGROSYS',
          style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold),
        ),
      ),
      pw.SizedBox(height: 8),
      for (final line in lines)
        pw.Padding(
          padding: const pw.EdgeInsets.symmetric(vertical: 2),
          child: pw.Text(line, style: const pw.TextStyle(fontSize: 9)),
        ),
    ];
    if (format.height.isFinite) {
      doc.addPage(
        pw.MultiPage(
          pageFormat: format,
          margin: const pw.EdgeInsets.all(8),
          maxPages: 200,
          theme: pw.ThemeData.withFont(base: font, bold: font),
          build: (_) => content,
        ),
      );
    } else {
      doc.addPage(
        pw.Page(
          pageFormat: roll,
          margin: const pw.EdgeInsets.all(8),
          theme: pw.ThemeData.withFont(base: font, bold: font),
          build: (_) => pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.stretch,
            children: content,
          ),
        ),
      );
    }
  } else {
    doc.addPage(
      pw.MultiPage(
        pageFormat: format,
        maxPages: 200,
        margin: const pw.EdgeInsets.all(20),
        theme: pw.ThemeData.withFont(base: font, bold: font),
        header: (_) => pw.Text(
          'AGROSYS · COMPROBANTE LOCAL',
          style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
        ),
        footer: (context) => pw.Text(
          '${receipt.sale.localFolio} · ${context.pageNumber}/${context.pagesCount}',
          style: const pw.TextStyle(fontSize: 8),
        ),
        build: (_) => [
          for (final line in receiptText(receipt).split('\n'))
            pw.Padding(
              padding: const pw.EdgeInsets.symmetric(vertical: 2),
              child: pw.Text(line, style: const pw.TextStyle(fontSize: 10)),
            ),
        ],
      ),
    );
  }
  return doc.save();
}

// Native print dialogs require finite paper dimensions. The printer driver
// can supply its configured roll size through onLayout.
const thermal80 = PdfPageFormat(80 * PdfPageFormat.mm, 297 * PdfPageFormat.mm);

Future<bool> systemTicketPrinter(StoredReceipt receipt) => Printing.layoutPdf(
  name: receipt.sale.localFolio,
  format: thermal80,
  usePrinterSettings: true,
  // Build the PDF locally, including its bundled font, for each selected paper size.
  onLayout: (format) => receiptPdf(receipt, format: format),
);

class ReceiptService {
  final SalesRepository sales;
  final TicketPrinter printer;
  final bool Function()? canPrint;
  ReceiptService(this.sales, {TicketPrinter? printer, this.canPrint})
    : printer = printer ?? systemTicketPrinter;
  Future<String> print(String saleId) async {
    if (sales.session.contextId != sales.db.contextId ||
        !sales.session.hasOfflineAccess ||
        !sales.session.permissions.contains('sale.print_local_ticket') ||
        await sales.catalog.isLocked() ||
        canPrint?.call() == false) {
      throw const SaleInputException(
        'Revalida tu autorización antes de imprimir.',
      );
    }
    final receipt = await sales.receipt(saleId);
    final id = const Uuid().v4();
    await sales.db
        .into(sales.db.receiptAttempts)
        .insert(
          ReceiptAttemptsCompanion.insert(
            id: id,
            saleId: saleId,
            attemptedAt: DateTime.now().toUtc().toIso8601String(),
            status: 'requested',
          ),
        );
    var status = 'failed';
    try {
      if (canPrint?.call() == false) {
        throw const SaleInputException('La sesión cambió antes de imprimir.');
      }
      status = await printer(receipt) ? 'submitted' : 'cancelled';
    } catch (_) {
      status = 'failed';
    }
    await (sales.db.update(
      sales.db.receiptAttempts,
    )..where((t) => t.id.equals(id))).write(
      ReceiptAttemptsCompanion(
        status: Value(status),
        message: Value(
          status == 'failed'
              ? 'No se pudo enviar el ticket a impresión.'
              : null,
        ),
      ),
    );
    return status;
  }
}
