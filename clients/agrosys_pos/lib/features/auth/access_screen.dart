import 'package:flutter/material.dart';

import '../../core/app_controller.dart';
import '../../ui/web_theme.dart';

class AccessScreen extends StatefulWidget {
  final AppController controller;
  const AccessScreen({super.key, required this.controller});
  @override
  State<AccessScreen> createState() => _AccessScreenState();
}

class _AccessScreenState extends State<AccessScreen> {
  final _form = GlobalKey<FormState>();
  final _email = TextEditingController(),
      _password = TextEditingController(),
      _code = TextEditingController();
  bool _recovery = false;
  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    _code.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_form.currentState!.validate()) return;
    try {
      await widget.controller.signIn(
        widget.controller.apiUrl,
        _email.text,
        _password.text,
      );
    } finally {
      _password.clear();
    }
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    return Scaffold(
      appBar: AppBar(title: const Text('Agrosys POS')),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(28),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Center(child: BrandLogo(width: 160)),
                    const SizedBox(height: 20),
                    Text(
                      c.storageFailure
                          ? 'No se pudo abrir el almacenamiento'
                          : c.session != null
                          ? 'Prepara tu consulta offline'
                          : 'Accede a tu sucursal',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 12),
                    if (c.error != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: Text(
                          c.error!,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.error,
                          ),
                        ),
                      ),
                    if (c.storageFailure)
                      const Text(
                        'No borres los datos de la aplicación. Cierra y abre para reintentar; si continúa, solicita soporte.',
                      ),
                    if (c.storageFailure && c.startupSessionFailure)
                      TextButton(
                        onPressed: c.busy
                            ? null
                            : () async {
                                final confirmed = await showDialog<bool>(
                                  context: context,
                                  builder: (dialogContext) => AlertDialog(
                                    title: const Text(
                                      'Volver a iniciar sesión',
                                    ),
                                    content: const Text(
                                      'Se cerrará la sesión guardada. Las ventas, pagos y pendientes locales se conservan en su contexto original y no se envían a otro servidor. Para recuperarlos debes volver a entrar al servidor y sucursal originales.',
                                    ),
                                    actions: [
                                      TextButton(
                                        onPressed: () =>
                                            Navigator.pop(dialogContext, false),
                                        child: const Text('Cancelar'),
                                      ),
                                      TextButton(
                                        onPressed: () =>
                                            Navigator.pop(dialogContext, true),
                                        child: const Text('Cerrar sesión'),
                                      ),
                                    ],
                                  ),
                                );
                                if (confirmed == true && context.mounted)
                                  await c.signOut();
                              },
                        child: const Text('Cerrar sesión y volver a entrar'),
                      ),
                    if (!c.storageFailure && c.session != null) ...[
                      Text(
                        '${c.session!.userName} · ${c.session!.branch.name}',
                      ),
                      const SizedBox(height: 12),
                      const Text(
                        'La primera descarga requiere conexión. Las descargas interrumpidas se pueden continuar y no reemplazan datos anteriores.',
                      ),
                      const SizedBox(height: 20),
                      FilledButton(
                        onPressed: c.busy
                            ? null
                            : c.session!.lease == null || c.session!.blocked
                            ? c.activate
                            : c.session!.hasOfflineAccess
                            ? c.synchronize
                            : c.renew,
                        child: Text(
                          c.session!.lease == null || c.session!.blocked
                              ? 'Activar o revalidar dispositivo'
                              : c.session!.hasOfflineAccess
                              ? 'Continuar descarga'
                              : 'Renovar autorización',
                        ),
                      ),
                      if (c.session!.lease != null && !c.session!.blocked)
                        TextButton(
                          onPressed: c.busy
                              ? null
                              : () => c.synchronize(full: true),
                          child: const Text('Reiniciar descarga completa'),
                        ),
                      TextButton(
                        onPressed: c.busy ? null : c.signOut,
                        child: const Text('Iniciar con otra cuenta'),
                      ),
                    ],
                    if (!c.storageFailure && c.session == null) ...[
                      if (c.signInStep == SignInStep.credentials)
                        Form(
                          key: _form,
                          child: Column(
                            children: [
                              const Text(
                                'Conéctate para activar el dispositivo. Después podrás consultar productos y clientes sin internet.',
                              ),
                              const SizedBox(height: 24),
                              TextFormField(
                                controller: _email,
                                enabled: !c.busy,
                                keyboardType: TextInputType.emailAddress,
                                autofillHints: const [AutofillHints.username],
                                decoration: const InputDecoration(
                                  labelText: 'Correo',
                                ),
                                validator: (v) => v == null || !v.contains('@')
                                    ? 'Indica tu correo.'
                                    : null,
                              ),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: _password,
                                enabled: !c.busy,
                                obscureText: true,
                                autocorrect: false,
                                enableSuggestions: false,
                                onFieldSubmitted: (_) =>
                                    c.busy ? null : _login(),
                                decoration: const InputDecoration(
                                  labelText: 'Contraseña',
                                ),
                                validator: (v) => v == null || v.isEmpty
                                    ? 'Indica tu contraseña.'
                                    : null,
                              ),
                              const SizedBox(height: 24),
                              SizedBox(
                                width: double.infinity,
                                child: FilledButton(
                                  onPressed: c.busy ? null : _login,
                                  child: const Text('Iniciar sesión'),
                                ),
                              ),
                            ],
                          ),
                        ),
                      if (c.signInStep == SignInStep.secondFactor) ...[
                        const Text(
                          'Completa la verificación en dos pasos. La solicitud vence a los 5 minutos.',
                        ),
                        const SizedBox(height: 20),
                        TextField(
                          controller: _code,
                          enabled: !c.busy,
                          decoration: InputDecoration(
                            labelText: _recovery
                                ? 'Código de recuperación'
                                : 'Código de autenticación',
                          ),
                          onSubmitted: (_) =>
                              c.verifySecondFactor(_code.text, _recovery),
                        ),
                        CheckboxListTile(
                          value: _recovery,
                          onChanged: c.busy
                              ? null
                              : (v) => setState(() => _recovery = v!),
                          title: const Text('Usar código de recuperación'),
                          contentPadding: EdgeInsets.zero,
                        ),
                        FilledButton(
                          onPressed: c.busy
                              ? null
                              : () =>
                                    c.verifySecondFactor(_code.text, _recovery),
                          child: const Text('Verificar'),
                        ),
                        TextButton(
                          onPressed: c.busy ? null : c.signOut,
                          child: const Text('Volver a iniciar sesión'),
                        ),
                      ],
                      if (c.signInStep == SignInStep.branch) ...[
                        const Text(
                          'Selecciona la sucursal de este dispositivo.',
                        ),
                        const SizedBox(height: 12),
                        for (final branch in c.branches)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: OutlinedButton.icon(
                              onPressed: c.busy
                                  ? null
                                  : () => c.selectBranch(branch),
                              icon: const Icon(Icons.storefront),
                              label: Text(branch.name),
                            ),
                          ),
                        if (c.branches.isEmpty)
                          const Text(
                            'No hay sucursales autorizadas. Contacta al administrador.',
                          ),
                        TextButton(
                          onPressed: c.busy ? null : c.signOut,
                          child: const Text('Usar otra cuenta'),
                        ),
                      ],
                    ],
                    if (c.busy) ...[
                      const SizedBox(height: 20),
                      const LinearProgressIndicator(),
                      const SizedBox(height: 8),
                      Text(
                        c.downloadedPages == 0
                            ? 'Conectando…'
                            : '${c.downloadedPages} páginas descargadas',
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
