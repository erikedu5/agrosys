import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:drift/drift.dart'
    show BooleanExpressionOperators, StringExpressionOperators;
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';
import '../../data/database.dart';

const cashLabels = {
  'sale': 'Ventas de contado',
  'credit_initial': 'Iniciales de crédito',
  'account_payment': 'Abonos de clientes',
  'refund': 'Reembolsos',
  'in': 'Entradas manuales',
  'out': 'Salidas manuales',
};

class CashScreen extends StatefulWidget {
  final AppController controller;
  const CashScreen({super.key, required this.controller});
  @override
  State<CashScreen> createState() => _CashScreenState();
}

class _CashScreenState extends State<CashScreen> {
  final _opening = TextEditingController(text: '0.00');
  final _amount = TextEditingController(), _reason = TextEditingController();
  final _counted = TextEditingController(), _notes = TextEditingController();
  Map<String, dynamic> _data = {
    'current': null,
    'history': [],
    'movements': [],
  };
  List<InventoryRequest> _pending = [];
  String _kind = 'out';
  String? _error, _notice;
  bool _loading = true,
      _writing = false,
      _creating = false,
      _confirmed = false,
      _cached = true;
  Map<String, dynamic>? get _current =>
      _data['current'] is Map ? object(_data['current']) : null;
  bool get _enabled =>
      !_loading &&
      !_writing &&
      !_creating &&
      _pending.isEmpty &&
      widget.controller.canWriteInventory('cash.manage');

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final c in [_opening, _amount, _reason, _counted, _notes]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load({bool keepError = false}) async {
    final c = widget.controller;
    final repo = c.repository;
    if (repo == null) return;
    setState(() {
      _loading = true;
      if (!keepError) _error = null;
    });
    try {
      final cached = await c.cachedCash();
      final pending =
          await (repo.db.select(repo.db.inventoryRequests)..where(
                (t) => t.path.like('cash/%') & t.status.equals('pending'),
              ))
              .get();
      if (!mounted || c.repository != repo) return;
      setState(() {
        _data = cached;
        _pending = pending;
        _cached = true;
      });
      if (c.online) {
        final fresh = await c.refreshCash();
        if (!mounted || c.repository != repo) return;
        setState(() {
          _data = fresh;
          _cached = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _moneyInput(TextEditingController controller) =>
      decimalText(decimalUnits(controller.text.trim()));
  Future<void> _submit(String action) async {
    if (!_enabled) return;
    setState(() {
      _creating = true;
      _error = null;
      _notice = null;
    });
    try {
      final c = widget.controller;
      Map<String, dynamic> body;
      if (action == 'open') {
        body = {'opening': _moneyInput(_opening)};
      } else if (action == 'movement') {
        final amount = _moneyInput(_amount);
        if (decimalUnits(amount) <= 0 || _reason.text.trim().isEmpty) {
          throw const FormatException(
            'Indica un importe positivo y un motivo.',
          );
        }
        body = {
          'session_id': _current!['id'],
          'kind': _kind,
          'amount': amount,
          'description': _reason.text.trim(),
        };
      } else {
        if (!_confirmed) {
          throw const FormatException(
            'Confirma el conteo y la sincronización de todos tus dispositivos.',
          );
        }
        await c.assertCashCanClose();
        final counted = _moneyInput(_counted);
        if (counted != _current!['expected'] && _notes.text.trim().isEmpty) {
          throw const FormatException(
            'Explica la diferencia de efectivo en las observaciones.',
          );
        }
        body = {
          'session_id': _current!['id'],
          'counted': counted,
          'expected': _current!['expected'],
          'notes': _notes.text.trim(),
          'confirmed': true,
        };
      }
      final id = await c.prepareInventoryRequest(
        'cash/$action',
        'cash.manage',
        body,
      );
      if (!mounted) return;
      await _retry(id);
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _creating = false);
    }
  }

  Future<void> _retry(String id) async {
    if (_writing) return;
    setState(() {
      _writing = true;
      _error = null;
      _notice = null;
    });
    try {
      await widget.controller.retryInventoryRequest(id);
      if (!mounted) return;
      setState(() {
        _notice = 'Operación de caja confirmada.';
        _confirmed = false;
      });
      _amount.clear();
      _reason.clear();
      _counted.clear();
      _notes.clear();
    } on ApiFailure catch (e) {
      if (mounted) {
        setState(
          () => _error =
              e.errors.values.expand((v) => v).firstOrNull ?? e.message,
        );
      }
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() => _writing = false);
        await _load(keepError: true);
      }
    }
  }

  Future<void> _print(Map<String, dynamic> cut) async {
    try {
      final font = pw.Font.ttf(
        await rootBundle.load('assets/fonts/Lato-Regular.ttf'),
      );
      final c = widget.controller;
      final text = [
        'CORTE DE CAJA · AGROSYS',
        '${c.session!.userName} · ${c.session!.branch.name}',
        'Folio: ${cut['id']}',
        'Apertura: ${_date(cut['openedAt'])}',
        'Cierre: ${_date(cut['closedAt'])}',
        'Fondo inicial: \$${cut['opening']}',
        for (final entry in object(cut['totals']).entries)
          '${cashLabels[entry.key] ?? entry.key}: \$${entry.value}',
        'Esperado: \$${cut['expected']}',
        'Contado: \$${cut['counted']}',
        'Diferencia: \$${cut['difference']}',
        if (cut['notes'] != null) cut['notes'].toString(),
      ].join('\n');
      await Printing.layoutPdf(
        name: 'Corte-${cut['id']}',
        onLayout: (format) async {
          final doc = pw.Document();
          doc.addPage(
            pw.MultiPage(
              pageFormat: PdfPageFormat.a4,
              theme: pw.ThemeData.withFont(base: font),
              build: (_) => [pw.Text(text)],
            ),
          );
          return doc.save();
        },
      );
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = 'El corte está guardado. No se pudo imprimir; puedes reintentarlo.',
        );
      }
    }
  }

  String _date(Object? value) {
    if (value == null) return '';
    return DateTime.parse(value.toString())
        .toLocal()
        .toString()
        .split('.')
        .first;
  }

  Widget _input(
    TextEditingController controller,
    String label, {
    bool amount = false,
    int max = 255,
  }) => TextField(
    controller: controller,
    enabled: _enabled,
    keyboardType: amount
        ? const TextInputType.numberWithOptions(decimal: true)
        : TextInputType.text,
    maxLength: amount ? null : max,
    decoration: InputDecoration(labelText: label, counterText: ''),
    onChanged: controller == _counted ? (_) => setState(() {}) : null,
  );
  Widget _card(List<Widget> children) => Card(
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: children,
      ),
    ),
  );
  Widget _heading(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 16),
    child: Text(text, style: Theme.of(context).textTheme.titleLarge),
  );

  @override
  Widget build(BuildContext context) {
    final current = _current;
    final c = widget.controller;
    final history = (_data['history'] as List? ?? []).map(object).toList();
    final movements = (_data['movements'] as List? ?? []).map(object).toList();
    String? difference;
    try {
      if (_counted.text.isNotEmpty && current != null) {
        difference = money(
          decimalUnits(_counted.text) -
              decimalUnits(current['expected'], signed: true),
        );
      }
    } catch (_) {
      /* The submit action validates incomplete input. */
    }
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Row(
          children: [
            Expanded(child: _heading('Apertura y corte de caja')),
            Tooltip(
              message:
                  'Caja de ${c.session!.userName} · ${c.session!.branch.name}. Apertura, movimientos y corte requieren conexión.\nAntes de cerrar, sincroniza las ventas de todos tus dispositivos. Abre la caja antes de realizar ventas.\nSi pagas a un proveedor con efectivo de esta caja, registra una salida manual.',
              triggerMode: TooltipTriggerMode.tap,
              child: const SizedBox(
                width: 44,
                height: 44,
                child: Icon(Icons.info_outline),
              ),
            ),
          ],
        ),
        if (_cached)
          const Text(
            'Datos guardados en este dispositivo. Conéctate para actualizar o manejar caja.',
          ),
        if (_loading || _writing) const LinearProgressIndicator(),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text(_error!, style: const TextStyle(color: Colors.red)),
          ),
        if (_notice != null)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text(_notice!, style: const TextStyle(color: Colors.green)),
          ),
        Align(
          alignment: Alignment.centerLeft,
          child: TextButton.icon(
            onPressed: _loading || _writing ? null : _load,
            icon: const Icon(Icons.refresh),
            label: const Text('Actualizar caja'),
          ),
        ),
        for (final request in _pending)
          _card([
            const Text(
              'Solicitud pendiente de confirmar. Reintenta antes de vender o crear otro movimiento.',
            ),
            Text(request.path),
            TextButton(
              onPressed: _writing || !c.canWriteInventory('cash.manage')
                  ? null
                  : () => _retry(request.operationId),
              child: const Text('Reintentar solicitud'),
            ),
          ]),
        if (current == null)
          _card([
            _heading('Abrir caja'),
            _input(_opening, 'Fondo inicial (\$)', amount: true),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _enabled ? () => _submit('open') : null,
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(56),
                textStyle: Theme.of(context).textTheme.labelLarge!
                    .copyWith(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              child: const Text('Abrir caja'),
            ),
          ])
        else ...[
          _card([
            _heading('Caja abierta · ${_date(current['openedAt'])}'),
            Text('Fondo inicial: \$${current['opening']}'),
            for (final entry in object(current['totals']).entries)
              Text('${cashLabels[entry.key] ?? entry.key}: \$${entry.value}'),
            const SizedBox(height: 12),
            Text(
              'Efectivo esperado: \$${current['expected']}',
              style: Theme.of(context).textTheme.titleLarge,
            ),
          ]),
          _card([
            _heading('Entrada o salida de efectivo'),
            DropdownButtonFormField<String>(
              initialValue: _kind,
              decoration: const InputDecoration(labelText: 'Tipo'),
              items: const [
                DropdownMenuItem(value: 'in', child: Text('Entrada')),
                DropdownMenuItem(value: 'out', child: Text('Salida')),
              ],
              onChanged: _enabled ? (v) => setState(() => _kind = v!) : null,
            ),
            const SizedBox(height: 12),
            _input(_amount, 'Importe (\$)', amount: true),
            const SizedBox(height: 12),
            _input(_reason, 'Motivo'),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _enabled ? () => _submit('movement') : null,
              child: const Text('Guardar movimiento'),
            ),
          ]),
          _card([
            _heading('Corte de caja'),
            _input(_counted, 'Efectivo contado (\$)', amount: true),
            if (difference != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text('Diferencia: $difference'),
              ),
            const SizedBox(height: 12),
            _input(
              _notes,
              'Observaciones (obligatorias si hay diferencia)',
              max: 2000,
            ),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _confirmed,
              onChanged: _enabled
                  ? (v) => setState(() => _confirmed = v ?? false)
                  : null,
              title: const Text(
                'Confirmo el conteo y la sincronización de todos mis dispositivos.',
              ),
            ),
            FilledButton(
              onPressed: _enabled ? () => _submit('close') : null,
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(56),
              ),
              child: const Text('Guardar corte y cerrar caja'),
            ),
          ]),
          _card([
            _heading('Últimos 100 movimientos'),
            if (movements.isEmpty) const Text('Sin movimientos todavía.'),
            for (final row in movements)
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(row['description'].toString()),
                subtitle: Text(
                  '${cashLabels[row['kind']]} · ${_date(row['date'])}',
                ),
                trailing: Text('\$${row['amount']}'),
              ),
          ]),
        ],
        _card([
          _heading('Últimos 20 cortes'),
          if (history.isEmpty) const Text('Aún no hay cortes guardados.'),
          for (final cut in history)
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(_date(cut['closedAt'])),
              subtitle: Text(
                'Esperado: \$${cut['expected']} · Contado: \$${cut['counted']}\nDiferencia: \$${cut['difference']}${cut['notes'] == null ? '' : '\n${cut['notes']}'}',
              ),
              trailing: IconButton(
                tooltip: 'Imprimir corte',
                onPressed: () => _print(cut),
                icon: const Icon(Icons.print_outlined),
              ),
            ),
        ]),
      ],
    );
  }
}
