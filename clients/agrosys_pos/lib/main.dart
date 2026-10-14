import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/app_controller.dart';
import 'core/session_vault.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final controller = AppController(vault: SessionVault(PlatformSecretStore()));
  runApp(
    ProviderScope(
      overrides: [appControllerProvider.overrideWithValue(controller)],
      child: const AgrosysPosApp(),
    ),
  );
  controller.initialize();
}
