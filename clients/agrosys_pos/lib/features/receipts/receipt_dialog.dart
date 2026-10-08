import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/app_controller.dart';
import '../sales/sales_repository.dart';
import 'receipt_service.dart';

Future<void> showReceipt(
  BuildContext context,
  AppController controller,
  StoredReceipt receipt,
) => showDialog<void>(
  context: context,
  builder: (_) => ReceiptDialog(controller: controller, receipt: receipt),
);

class ReceiptDialog extends StatefulWidget {
  final AppController controller;
  final StoredReceipt receipt;
  const ReceiptDialog({
    super.key,
    required this.controller,
    required this.receipt,
  });
  @override
  State<ReceiptDialog> createState() => _ReceiptDialogState();
}

class _ReceiptDialogState extends State<ReceiptDialog> {
  bool _printing = false;
  String? _message;
  bool get _authorized =>
      widget.controller.canConsult &&
      widget.controller.session?.contextId == widget.receipt.sale.contextId;
  Future<void> _print() async {
    if (_printing || !_authorized) return;
    setState(() {
      _printing = true;
      _message = null;
    });
    try {
      final result = await ReceiptService(
        widget.controller.sales!,
        canPrint: () => _authorized,
      ).print(widget.receipt.sale.id);
      if (!mounted) return;
      setState(
        () => _message = switch (result) {
          'submitted' => 'Ticket enviado al sistema de impresión. Verifica la salida en la impresora.',
          'cancelled' => 'Impresión cancelada. La venta sigue guardada.',
          _ => 'No se pudo imprimir. La venta sigue guardada; puedes reintentar desde el historial.',
        },
      );
    } catch (_) {
      if (mounted) {
        setState(
          () => _message = 'No se pudo registrar o imprimir el ticket. La venta permanece guardada.',
        );
      }
    } finally {
      if (mounted) setState(() => _printing = false);
    }
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: widget.controller,
    builder: (context, _) => AlertDialog(
      title: const Text('Comprobante de venta'),
      content: SizedBox(
        width: 520,
        child: SingleChildScrollView(
          child: _authorized
              ? Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Padding(
                      padding: EdgeInsets.only(bottom: 12),
                      child: Text(
                        'Ticket térmico 80 mm · elige la impresora USB instalada en el sistema.',
                      ),
                    ),
                    SelectableText(receiptText(widget.receipt)),
                    if (_message != null) ...[
                      const SizedBox(height: 16),
                      Text(_message!),
                    ],
                  ],
                )
              : const Text(
                  'Revalida tu autorización para consultar este comprobante.',
                ),
        ),
      ),
      actions: [
        if (_authorized)
          TextButton.icon(
            onPressed: _printing
                ? null
                : () => Clipboard.setData(
                    ClipboardData(text: receiptText(widget.receipt)),
                  ),
            icon: const Icon(Icons.copy),
            label: const Text('Copiar'),
          ),
        if (_authorized &&
            widget.controller.session!.permissions.contains(
              'sale.print_local_ticket',
            ))
          FilledButton.icon(
            onPressed: _printing ? null : _print,
            icon: const Icon(Icons.print_outlined),
            label: Text(_printing ? 'Enviando…' : 'Imprimir / reimprimir'),
          ),
        TextButton(
          onPressed: _printing ? null : () => Navigator.pop(context),
          child: const Text('Cerrar'),
        ),
      ],
    ),
  );
}
