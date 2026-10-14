import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/app_controller.dart';
import 'ui/web_theme.dart';
import 'features/auth/access_screen.dart';
import 'features/catalog/catalog_screen.dart';

final appControllerProvider = Provider<AppController>(
  (ref) => throw StateError('Provide AppController at startup.'),
);

class AgrosysPosApp extends ConsumerStatefulWidget {
  const AgrosysPosApp({super.key});
  @override
  ConsumerState<AgrosysPosApp> createState() => _AgrosysPosAppState();
}

class _AgrosysPosAppState extends ConsumerState<AgrosysPosApp>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    ref
        .read(appControllerProvider)
        .setForeground(state == AppLifecycleState.resumed);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = ref.watch(appControllerProvider);
    return MaterialApp(
      title: 'Agrosys POS',
      debugShowCheckedModeBanner: false,
      theme: WebTheme.light,
      home: ListenableBuilder(
        listenable: controller,
        builder: (context, _) {
          if (controller.starting) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }
          return controller.canConsult
              ? CatalogScreen(controller: controller)
              : AccessScreen(controller: controller);
        },
      ),
    );
  }
}
