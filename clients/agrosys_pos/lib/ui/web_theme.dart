import 'package:flutter/material.dart';

// Tokens from AppLayout.vue and Venta.vue; bundled brand assets work offline.
abstract final class WebTheme {
  static const blue = Color(0xff2563eb);
  static const green = Color(0xff15803d);
  static const ink = Color(0xff111827);
  static const muted = Color(0xff4b5563);
  static const border = Color(0xffe5e7eb);
  static const canvas = Color(0xfff3f4f6);
  static ThemeData get light => ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(seedColor: blue)
        .copyWith(primary: blue, secondary: green, surface: Colors.white),
    scaffoldBackgroundColor: canvas,
    textTheme: ThemeData.light().textTheme.apply(
      bodyColor: ink,
      displayColor: ink,
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: ink,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: border),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xffd1d5db)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xffd1d5db)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: blue, width: 2),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size(44, 52),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size(44, 44),
        foregroundColor: ink,
        side: const BorderSide(color: Color(0xffd1d5db)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    ),
    dividerTheme: const DividerThemeData(color: border),
  );
}

class BrandLogo extends StatelessWidget {
  final double width;
  const BrandLogo({super.key, this.width = 100});
  @override
  Widget build(BuildContext context) => Image.asset(
    'assets/brand/agrosyslogo-word.png',
    width: width,
    semanticLabel: 'AgroSys',
  );
}

class StatusPill extends StatelessWidget {
  final String text;
  final bool warning;
  const StatusPill(this.text, {super.key, this.warning = false});
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
    decoration: BoxDecoration(
      color: warning ? const Color(0xfffffbeb) : const Color(0xffdcfce7),
      borderRadius: BorderRadius.circular(24),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(
          Icons.circle,
          size: 8,
          color: warning ? const Color(0xffd97706) : WebTheme.green,
        ),
        const SizedBox(width: 8),
        Text(
          text,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: warning ? const Color(0xff92400e) : WebTheme.green,
          ),
        ),
      ],
    ),
  );
}
