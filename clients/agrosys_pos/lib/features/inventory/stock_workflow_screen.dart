import 'package:flutter/material.dart';
import 'package:drift/drift.dart'
    show BooleanExpressionOperators, StringExpressionOperators;

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';
import '../../data/database.dart';
import 'stock_document_editor.dart';

class StockWorkflowScreen extends StatefulWidget {
  final AppController controller;
  final String kind;
  const StockWorkflowScreen({
    super.key,
    required this.controller,
    required this.kind,
  });
  @override
  State<StockWorkflowScreen> createState() => _StockWorkflowScreenState();
}

class _StockWorkflowScreenState extends State<StockWorkflowScreen> {
  final _query = TextEditingController(), _payment = TextEditingController();
  List<Map<String, dynamic>> _rows = [];
  List<InventoryRequest> _pending = [];
  Map<String, dynamic>? _selected;
  String? _error, _notice, _operationId;
  bool _loading = true, _writing = false, _creating = false, _more = false;
  int _page = 0, _request = 0, _generation = -1;
  bool get _purchase => widget.kind == 'purchases';
  String get _prefix => _purchase ? 'purchase' : 'transfer';
  String get _title => _purchase ? 'Compras' : 'Transferencias';
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(StockWorkflowScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_generation != widget.controller.generation &&
        !_writing &&
        !_creating) {
      _load();
    }
  }

  @override
  void dispose() {
    _query.dispose();
    _payment.dispose();
    super.dispose();
  }

  Future<void> _load({bool append = false}) async {
    final c = widget.controller;
    final repo = c.repository;
    if (repo == null || !c.hasPermission('$_prefix.read')) return;
    final request = ++_request;
    _generation = c.generation;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final pending =
          await (repo.db.select(repo.db.inventoryRequests)..where(
                (t) =>
                    t.status.equals('pending') &
                    t.path.like('${widget.kind}/%'),
              ))
              .get();
      if (!mounted || request != _request || c.repository != repo) return;
      setState(() => _pending = pending);
      final data = await c.stockWorkflowData(
        '${widget.kind}/list',
        '$_prefix.read',
        body: {'q': _query.text.trim(), 'page': append ? _page + 1 : 1},
      );
      if (!mounted || request != _request) return;
      final selected = _selected == null
          ? null
          : await c.stockWorkflowData(
              '${widget.kind}/${_selected!['id']}/detail',
              '$_prefix.read',
            );
      if (!mounted || request != _request) return;
      setState(() {
        final rows = (data['items'] as List).map(object).toList();
        _rows = append ? [..._rows, ...rows] : rows;
        _page = data['page'] as int;
        _more = data['hasMore'] == true;
        if (selected != null) _selected = object(selected[_prefix]);
      });
    } catch (e) {
      if (mounted && request == _request) {
        setState(
          () => _error = e is ApiFailure
              ? e.message
              : 'No se pudo consultar el módulo.',
        );
      }
    } finally {
      if (mounted && request == _request) setState(() => _loading = false);
    }
  }

  Future<void> _select(String id) async {
    final request = ++_request;
    setState(() {
      _loading = true;
      _error = null;
      _notice = null;
    });
    try {
      final data = await widget.controller.stockWorkflowData(
        '${widget.kind}/$id/detail',
        '$_prefix.read',
      );
      if (!mounted || request != _request) return;
      setState(() {
        _selected = object(data[_prefix]);
        _operationId = null;
        _payment.clear();
      });
    } catch (e) {
      if (mounted && request == _request) setState(() => _error = e.toString());
    } finally {
      if (mounted && request == _request) setState(() => _loading = false);
    }
  }

  Future<void> _confirmWrite() async {
    final permission = '$_prefix.${_purchase ? 'payment' : 'receive'}';
    if (_writing ||
        _selected == null ||
        !widget.controller.canWriteInventory(permission)) {
      return;
    }
    final row = _selected!;
    if (_operationId == null) {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(_purchase ? 'Confirmar abono' : 'Confirmar recepción'),
          content: Text(
            _purchase
                ? 'Registrar \$${_payment.text.trim()} a la compra de ${row['supplier']}.'
                : 'Recibir ${row['folio']} de ${row['origin']} en ${row['destination']}. Se moverán todas las cantidades indicadas.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              key: const Key('stock-confirm-write'),
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Confirmar'),
            ),
          ],
        ),
      );
      if (!mounted || confirmed != true) return;
    }
    setState(() {
      _writing = true;
      _error = null;
    });
    try {
      _operationId ??= await widget.controller.prepareInventoryRequest(
        '${widget.kind}/${row['id']}/${_purchase ? 'payment' : 'receive'}',
        permission,
        _purchase
            ? {'amount': _payment.text.trim(), 'expected_debt': row['debt']}
            : {'confirmed': true},
      );
      final result = await widget.controller.retryInventoryRequest(
        _operationId!,
      );
      if (!mounted) return;
      setState(() {
        _operationId = null;
        _notice =
            result['syncWarning'] as String? ??
            (_purchase ? 'Abono registrado.' : 'Transferencia recibida.');
        _payment.clear();
      });
      await _load();
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e is ApiFailure
              ? [e.message, ...e.errors.values.expand((v) => v)].join('\n')
              : e.toString();
          if (e is ApiFailure && [404, 409, 422].contains(e.status)) {
            _operationId = null;
          }
        });
      }
    } finally {
      if (mounted) setState(() => _writing = false);
    }
  }

  Future<void> _retry(InventoryRequest row) async {
    if (_writing) return;
    setState(() {
      _writing = true;
      _error = null;
    });
    try {
      final result = await widget.controller.retryInventoryRequest(
        row.operationId,
      );
      if (mounted) {
        setState(
          () => _notice =
              result['syncWarning'] as String? ?? 'Solicitud confirmada.',
        );
      }
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() => _writing = false);
        await _load();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    if (!c.hasPermission('$_prefix.read')) {
      return const Center(child: Text('Módulo no autorizado.'));
    }
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  _title,
                  style: Theme.of(context).textTheme.headlineMedium,
                ),
              ),
              if (!_creating && c.hasPermission('$_prefix.create'))
                IconButton(
                  key: ValueKey('${widget.kind}-create'),
                  tooltip: _purchase ? 'Nueva compra' : 'Nueva transferencia',
                  icon: const Icon(Icons.add),
                  onPressed: c.canWriteInventory('$_prefix.create') && !_writing
                      ? () => setState(() {
                          _creating = true;
                          _notice = null;
                          _error = null;
                        })
                      : null,
                ),
              if (!_creating)
                IconButton(
                  tooltip: 'Actualizar',
                  onPressed: c.online && !_loading && !_writing ? _load : null,
                  icon: const Icon(Icons.refresh),
                ),
            ],
          ),
          Text('Sucursal: ${c.session!.branch.name}'),
          if (!c.online)
            const Text(
              'La consulta y el registro de este módulo requieren conexión.',
            ),
          if (_error != null)
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          if (_notice != null) Text(_notice!),
          if (!_creating && _pending.isNotEmpty)
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 130),
              child: ListView(
                shrinkWrap: true,
                children: [
                  for (final row in _pending)
                    if (c.hasPermission(row.permission))
                      ListTile(
                        title: Text(
                          'Solicitud de ${_purchase ? 'compra' : 'transferencia'} por confirmar',
                        ),
                        trailing: TextButton(
                          onPressed:
                              !_writing && c.canWriteInventory(row.permission)
                              ? () => _retry(row)
                              : null,
                          child: const Text('Reintentar solicitud'),
                        ),
                      ),
                ],
              ),
            ),
          const SizedBox(height: 12),
          Expanded(
            child: _creating
                ? StockDocumentEditor(
                    key: ValueKey('${widget.kind}-editor'),
                    controller: c,
                    kind: widget.kind,
                    onCancel: () {
                      setState(() => _creating = false);
                      _load();
                    },
                    onSaved: (result) {
                      setState(() {
                        _creating = false;
                        _selected = null;
                        _notice =
                            result['syncWarning'] as String? ??
                            'Registro guardado.';
                      });
                      _load();
                    },
                  )
                : _loading
                ? const Center(child: CircularProgressIndicator())
                : _selected != null
                ? _detail(c)
                : _list(),
          ),
        ],
      ),
    );
  }

  Widget _list() => Column(
    children: [
      TextField(
        key: const Key('stock-list-search'),
        controller: _query,
        decoration: InputDecoration(
          labelText: _purchase ? 'Buscar proveedor' : 'Buscar folio',
          suffixIcon: IconButton(
            onPressed: widget.controller.online ? _load : null,
            icon: const Icon(Icons.search),
          ),
        ),
        onSubmitted: (_) => _load(),
      ),
      Expanded(
        child: ListView(
          children: [
            for (final row in _rows)
              Card(
                child: ListTile(
                  key: ValueKey('stock-document-${row['id']}'),
                  title: Text(
                    (_purchase ? row['supplier'] : row['folio']) as String,
                  ),
                  subtitle: Text(
                    _purchase
                        ? '${row['status']} · Total \$${row['total']} · Saldo \$${row['debt']}'
                        : '${row['origin']} → ${row['destination']} · ${row['status']}',
                  ),
                  onTap: widget.controller.online
                      ? () => _select(row['id'] as String)
                      : null,
                ),
              ),
            if (_rows.isEmpty)
              const Text('No hay registros para esta búsqueda.'),
            if (_more)
              TextButton(
                onPressed: widget.controller.online && !_loading
                    ? () => _load(append: true)
                    : null,
                child: const Text('Cargar más'),
              ),
          ],
        ),
      ),
    ],
  );
  Widget _detail(AppController c) {
    final row = _selected!;
    final permission = '$_prefix.${_purchase ? 'payment' : 'receive'}';
    final canAct = _purchase
        ? decimalUnits(row['debt']) > 0
        : row['status'] == 'pendiente' &&
              row['destinationId'] == c.session!.branch.id;
    return ListView(
      key: const Key('stock-detail-scroll'),
      children: [
        Align(
          alignment: Alignment.centerLeft,
          child: TextButton.icon(
            onPressed: _writing
                ? null
                : () {
                    ++_request;
                    setState(() {
                      _selected = null;
                      _operationId = null;
                      _error = null;
                    });
                    _load();
                  },
            icon: const Icon(Icons.arrow_back),
            label: Text('Volver a $_title'),
          ),
        ),
        Text(
          (_purchase ? row['supplier'] : row['folio']) as String,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        Text('Estado: ${row['status']}'),
        Text('Fecha: ${row['date'] ?? ''}'),
        if (_purchase) ...[
          Text('Total: \$${row['total']}'),
          Text('Saldo: \$${row['debt']}'),
          Text('Vencimiento: ${row['dueDate']}'),
        ] else ...[
          Text('Origen: ${row['origin']}'),
          Text('Destino: ${row['destination']}'),
          Text('Observaciones: ${row['notes']}'),
          if (row['receivedAt'] != null)
            Text('Recepción: ${row['receivedAt']}'),
        ],
        const SizedBox(height: 12),
        for (final item in (row['items'] as List).map(object))
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text('${item['name']} · ${item['size']}'),
            subtitle: Text(
              '${item['quantity']} unidades${_purchase ? ' · \$${item['cost']} por unidad' : ''}',
            ),
          ),
        if (_purchase) ...[
          const Text('Abonos'),
          for (final payment in (row['payments'] as List).map(object))
            Text('\$${payment['amount']} · ${payment['date']}'),
        ],
        if (canAct && c.hasPermission(permission)) ...[
          if (_purchase)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: TextField(
                key: const Key('stock-payment-amount'),
                controller: _payment,
                enabled: !_writing && _operationId == null,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Cantidad a abonar',
                  border: OutlineInputBorder(),
                ),
              ),
            ),
          Align(
            alignment: Alignment.centerLeft,
            child: FilledButton(
              key: const Key('stock-document-action'),
              onPressed: !_writing && c.canWriteInventory(permission)
                  ? _confirmWrite
                  : null,
              child: Text(
                _writing
                    ? 'Confirmando…'
                    : _operationId != null
                    ? 'Reintentar solicitud'
                    : _purchase
                    ? 'Registrar abono'
                    : 'Recibir transferencia',
              ),
            ),
          ),
        ],
      ],
    );
  }
}
