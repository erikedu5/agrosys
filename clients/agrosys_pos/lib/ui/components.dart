import 'package:flutter/material.dart';

import 'web_theme.dart';

// Componentes equivalentes a los de la web (resources/js/Components y las
// clases ui-* de resources/css/app.css): PageHeader, SearchField, chips de
// filtro, RecordCard, ui-badge, EmptyState y la cuadrícula ui-grid.

abstract final class Palette {
  static const gray50 = Color(0xfff9fafb);
  static const gray100 = Color(0xfff3f4f6);
  static const gray200 = Color(0xffe5e7eb);
  static const gray300 = Color(0xffd1d5db);
  static const gray500 = Color(0xff6b7280);
  static const gray600 = Color(0xff4b5563);
  static const gray700 = Color(0xff374151);
  static const gray900 = Color(0xff111827);
  static const green600 = Color(0xff16a34a);
  static const amber400 = Color(0xfffbbf24);
  static const red500 = Color(0xffef4444);
  static const red700 = Color(0xffb91c1c);
  static const blue50 = Color(0xffeff6ff);
  static const indigo500 = Color(0xff6366f1);
}

enum Tone {
  gray(Palette.gray100, Palette.gray700),
  green(Color(0xfff0fdf4), Color(0xff166534)),
  amber(Color(0xfffef3c7), Color(0xff92400e)),
  red(Color(0xfffef2f2), Color(0xff991b1b)),
  blue(Color(0xffdbeafe), Color(0xff1e40af));

  final Color background, foreground;
  const Tone(this.background, this.foreground);
}

/// Existencia en centésimas: agotado (≤0), baja (≤5) o normal, como en Inventario.vue.
Tone stockTone(int units) => units <= 0
    ? Tone.red
    : units <= 500
    ? Tone.amber
    : Tone.green;
Color stockAccent(int units) => switch (stockTone(units)) {
  Tone.red => Palette.red500,
  Tone.amber => Palette.amber400,
  _ => Palette.green600,
};
String stockBadgeText(int units, String quantity) =>
    units <= 0 ? 'Agotado' : '$quantity en exist.';

/// ui-badge
class UiBadge extends StatelessWidget {
  final String text;
  final Tone tone;
  const UiBadge(this.text, {super.key, this.tone = Tone.gray});
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
    decoration: BoxDecoration(
      color: tone.background,
      borderRadius: BorderRadius.circular(6),
    ),
    child: Text(
      text,
      style: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w700,
        color: tone.foreground,
      ),
    ),
  );
}

/// PageHeader.vue: título, subtítulo y acciones (a todo lo ancho en móvil).
class PageHeader extends StatelessWidget {
  final String title;
  final String? subtitle;
  final List<Widget> actions;
  const PageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.actions = const [],
  });
  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final heading = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 20,
              height: 1.3,
              fontWeight: FontWeight.w700,
              color: Palette.gray900,
            ),
          ),
          if (subtitle != null && subtitle!.isNotEmpty)
            Text(
              subtitle!,
              style: const TextStyle(fontSize: 14, color: Palette.gray600),
            ),
        ],
      );
      if (actions.isEmpty) return heading;
      if (constraints.maxWidth >= 640) {
        return Row(
          children: [
            Expanded(child: heading),
            const SizedBox(width: 12),
            for (final (i, action) in actions.indexed) ...[
              if (i > 0) const SizedBox(width: 8),
              action,
            ],
          ],
        );
      }
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          heading,
          const SizedBox(height: 8),
          Row(
            children: [
              for (final (i, action) in actions.indexed) ...[
                if (i > 0) const SizedBox(width: 8),
                // Los botones de icono conservan su tamaño; el resto se reparte el ancho.
                action is IconButton ? action : Expanded(child: action),
              ],
            ],
          ),
        ],
      );
    },
  );
}

/// SearchField.vue: campo de 56px con lupa.
class UiSearchField extends StatelessWidget {
  final TextEditingController controller;
  final String hint;
  final FocusNode? focusNode;
  final ValueChanged<String>? onChanged, onSubmitted;
  final VoidCallback? onClear;
  final Widget? trailing;
  const UiSearchField({
    super.key,
    required this.controller,
    required this.hint,
    this.focusNode,
    this.onChanged,
    this.onSubmitted,
    this.onClear,
    this.trailing,
  });
  @override
  Widget build(BuildContext context) {
    OutlineInputBorder border(Color color) => OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: BorderSide(color: color, width: 2),
    );
    return TextField(
      controller: controller,
      focusNode: focusNode,
      onChanged: onChanged,
      onSubmitted: onSubmitted,
      textInputAction: TextInputAction.search,
      style: const TextStyle(fontSize: 17, color: Palette.gray900),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(fontSize: 17, color: Palette.gray500),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(vertical: 16),
        prefixIcon: const Padding(
          padding: EdgeInsets.only(left: 14, right: 8),
          child: Icon(Icons.search, size: 24, color: Palette.gray500),
        ),
        prefixIconConstraints: const BoxConstraints(minWidth: 46),
        suffixIcon:
            trailing ??
            (onClear == null
                ? null
                : IconButton(
                    tooltip: 'Limpiar búsqueda',
                    icon: const Icon(Icons.clear, color: Palette.gray500),
                    onPressed: onClear,
                  )),
        border: border(Palette.gray300),
        enabledBorder: border(Palette.gray300),
        focusedBorder: border(Palette.indigo500),
      ),
    );
  }
}

/// Chips de filtro de Inventario.vue: píldoras de 44px, la activa en gris oscuro.
class FilterChips<T> extends StatelessWidget {
  final List<(T, String, Color?)> options;
  final T selected;
  final ValueChanged<T> onSelected;
  const FilterChips({
    super.key,
    required this.options,
    required this.selected,
    required this.onSelected,
  });
  @override
  Widget build(BuildContext context) => SingleChildScrollView(
    scrollDirection: Axis.horizontal,
    child: Row(
      children: [
        for (final (value, label, dot) in options)
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              selected: value == selected,
              showCheckmark: false,
              onSelected: (_) => onSelected(value),
              backgroundColor: Colors.white,
              selectedColor: Palette.gray900,
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
              shape: StadiumBorder(
                side: BorderSide(
                  color: value == selected ? Palette.gray900 : Palette.gray300,
                ),
              ),
              label: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (dot != null) ...[
                    Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                        color: dot,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: value == selected ? Colors.white : Palette.gray700,
                    ),
                  ),
                ],
              ),
            ),
          ),
      ],
    ),
  );
}

class RecordField {
  final String label;
  final String? value;
  final bool wide;
  const RecordField(this.label, this.value, {this.wide = false});
}

/// RecordCard.vue: encabezado, insignias, cuerpo, campos en dos columnas y acciones.
class RecordCard extends StatelessWidget {
  final String? eyebrow, subtitle;
  final String title;
  final List<Widget> badges, actions;
  final List<RecordField> fields;
  final Widget? child;
  final Color? accent;
  final bool selected;
  final VoidCallback? onTap;
  const RecordCard({
    super.key,
    required this.title,
    this.eyebrow,
    this.subtitle,
    this.badges = const [],
    this.fields = const [],
    this.actions = const [],
    this.child,
    this.accent,
    this.selected = false,
    this.onTap,
  });

  List<Widget> _fieldRows() {
    Widget cell(RecordField f) => Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          f.label,
          style: const TextStyle(fontSize: 12, color: Palette.gray600),
        ),
        Text(
          f.value == null || f.value!.isEmpty ? '—' : f.value!,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: Palette.gray900,
          ),
        ),
      ],
    );
    final rows = <Widget>[];
    RecordField? pending;
    void flush([RecordField? next]) {
      rows.add(
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: cell(pending!)),
            const SizedBox(width: 16),
            Expanded(child: next == null ? const SizedBox() : cell(next)),
          ],
        ),
      );
      pending = null;
    }

    for (final field in fields) {
      if (field.wide) {
        if (pending != null) flush();
        rows.add(cell(field));
      } else if (pending == null) {
        pending = field;
      } else {
        flush(field);
      }
    }
    if (pending != null) flush();
    return [
      for (final (i, row) in rows.indexed) ...[
        if (i > 0) const SizedBox(height: 10),
        row,
      ],
    ];
  }

  @override
  Widget build(BuildContext context) {
    // En filas de la cuadrícula la tarjeta llena la altura y deja las acciones al pie.
    final stretch =
        context.dependOnInheritedWidgetOfExactType<_StretchScope>() != null;
    final content = Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: stretch ? MainAxisSize.max : MainAxisSize.min,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (eyebrow != null && eyebrow!.isNotEmpty)
                      Text(
                        eyebrow!.toUpperCase(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 12,
                          letterSpacing: 0.6,
                          fontWeight: FontWeight.w600,
                          color: Palette.gray600,
                        ),
                      ),
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 17,
                        height: 1.3,
                        fontWeight: FontWeight.w700,
                        color: Palette.gray900,
                      ),
                    ),
                    if (subtitle != null && subtitle!.isNotEmpty)
                      Text(
                        subtitle!,
                        style: const TextStyle(
                          fontSize: 14,
                          color: Palette.gray600,
                        ),
                      ),
                  ],
                ),
              ),
              if (badges.isNotEmpty) ...[
                const SizedBox(width: 12),
                // Una insignia larga se parte en renglones en lugar de aplastar el título.
                ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 160),
                  child: Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    alignment: WrapAlignment.end,
                    children: badges,
                  ),
                ),
              ],
            ],
          ),
          if (child != null) ...[const SizedBox(height: 12), child!],
          if (fields.isNotEmpty) ...[
            const SizedBox(height: 12),
            ..._fieldRows(),
          ],
          if (stretch) const Spacer(),
          if (actions.isNotEmpty) ...[
            const SizedBox(height: 12),
            const Divider(height: 1, color: Palette.gray100),
            const SizedBox(height: 12),
            Wrap(spacing: 8, runSpacing: 8, children: actions),
          ],
        ],
      ),
    );
    final radius = BorderRadius.circular(12);
    return Material(
      color: selected ? Palette.blue50 : Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: radius,
        side: BorderSide(
          color: selected ? WebTheme.blue : Palette.gray200,
          width: selected ? 2 : 1,
        ),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: accent == null
            ? content
            : Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: stretch ? MainAxisSize.max : MainAxisSize.min,
                children: [
                  Container(height: 4, color: accent),
                  if (stretch) Expanded(child: content) else content,
                ],
              ),
      ),
    );
  }
}

class _StretchScope extends InheritedWidget {
  const _StretchScope({required super.child});
  @override
  bool updateShouldNotify(_StretchScope oldWidget) => false;
}

/// ui-btn ui-btn-icon: botón cuadrado de 44px con borde.
class IconAction extends StatelessWidget {
  final IconData icon;
  final String tooltip;
  final VoidCallback? onPressed;
  final bool danger;
  const IconAction({
    super.key,
    required this.icon,
    required this.tooltip,
    required this.onPressed,
    this.danger = false,
  });
  @override
  Widget build(BuildContext context) => Tooltip(
    message: tooltip,
    child: OutlinedButton(
      onPressed: onPressed,
      style: OutlinedButton.styleFrom(
        fixedSize: const Size(44, 44),
        minimumSize: const Size(44, 44),
        padding: EdgeInsets.zero,
        foregroundColor: danger ? Palette.red700 : Palette.gray700,
        side: BorderSide(
          color: danger ? const Color(0xfffecaca) : Palette.gray300,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      child: Icon(icon, size: 20, semanticLabel: tooltip),
    ),
  );
}

/// EmptyState.vue
class EmptyState extends StatelessWidget {
  final String title;
  final String? text;
  final Widget? child;
  const EmptyState({super.key, required this.title, this.text, this.child});
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 48),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          title,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w700,
            color: Palette.gray900,
          ),
        ),
        if (text != null) ...[
          const SizedBox(height: 6),
          Text(
            text!,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 15, color: Palette.gray600),
          ),
        ],
        if (child != null) ...[const SizedBox(height: 16), child!],
      ],
    ),
  );
}

/// Texto secundario (ui-muted).
class Muted extends StatelessWidget {
  final String text;
  const Muted(this.text, {super.key});
  @override
  Widget build(BuildContext context) =>
      Text(text, style: const TextStyle(fontSize: 14, color: Palette.gray600));
}

/// ui-grid: 1 columna en móvil, 2 en tableta y 3 en escritorio, con tarjetas
/// de la misma altura por fila. Construye las filas bajo demanda.
class RecordGrid extends StatelessWidget {
  final int itemCount;
  final IndexedWidgetBuilder itemBuilder;
  final List<Widget> header, footer;
  final Key? scrollKey;
  const RecordGrid({
    super.key,
    required this.itemCount,
    required this.itemBuilder,
    this.header = const [],
    this.footer = const [],
    this.scrollKey,
  });
  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final columns = constraints.maxWidth >= 1100
          ? 3
          : constraints.maxWidth >= 600
          ? 2
          : 1;
      final rows = (itemCount + columns - 1) ~/ columns;
      return ListView.builder(
        key: scrollKey,
        padding: const EdgeInsets.only(bottom: 16),
        itemCount: header.length + rows + footer.length,
        itemBuilder: (context, index) {
          if (index < header.length) return header[index];
          final row = index - header.length;
          if (row >= rows) return footer[row - rows];
          final cells = [
            for (var c = 0; c < columns; c++)
              row * columns + c < itemCount
                  ? itemBuilder(context, row * columns + c)
                  : null,
          ];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: columns == 1
                ? cells.first
                : IntrinsicHeight(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        for (final (i, cell) in cells.indexed) ...[
                          if (i > 0) const SizedBox(width: 12),
                          Expanded(
                            child: _StretchScope(
                              child: cell ?? const SizedBox(),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
          );
        },
      );
    },
  );
}

/// ui-alert (ámbar), ui-alert-red y ui-alert-blue.
class UiAlert extends StatelessWidget {
  final String text;
  final Tone tone;
  const UiAlert(this.text, {super.key, this.tone = Tone.amber});
  @override
  Widget build(BuildContext context) {
    final (background, border, foreground) = switch (tone) {
      Tone.red => (
        const Color(0xfffef2f2),
        const Color(0xfffecaca),
        const Color(0xff991b1b),
      ),
      Tone.blue => (
        Palette.blue50,
        const Color(0xffbfdbfe),
        const Color(0xff1e3a8a),
      ),
      Tone.green => (
        const Color(0xfff0fdf4),
        const Color(0xffbbf7d0),
        const Color(0xff166534),
      ),
      _ => (
        const Color(0xfffffbeb),
        const Color(0xfffde68a),
        const Color(0xff78350f),
      ),
    };
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: background,
        border: Border.all(color: border),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(text, style: TextStyle(fontSize: 14, color: foreground)),
    );
  }
}

/// Importe destacado de la tarjeta (text-xl font-extrabold) con su etiqueta.
class AmountLine extends StatelessWidget {
  final String amount;
  final String? label;
  const AmountLine(this.amount, {super.key, this.label});
  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.end,
    children: [
      Expanded(
        child: Text(
          amount,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: Palette.gray900,
          ),
        ),
      ),
      if (label != null)
        Text(
          label!,
          style: const TextStyle(fontSize: 12, color: Palette.gray600),
        ),
    ],
  );
}

/// Sección con título dentro de una tarjeta blanca (ui-card).
class SectionCard extends StatelessWidget {
  final String? title;
  final List<Widget> children;
  const SectionCard({super.key, this.title, required this.children});
  @override
  // Material (no DecoratedBox) para que ListTile y botones pinten su tinta encima.
  Widget build(BuildContext context) => Material(
    color: Colors.white,
    shape: RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(12),
      side: const BorderSide(color: Palette.gray200),
    ),
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (title != null) ...[
            Text(
              title!,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: Palette.gray900,
              ),
            ),
            const SizedBox(height: 8),
          ],
          ...children,
        ],
      ),
    ),
  );
}

/// ui-row: renglón con borde superior para listas dentro de una tarjeta.
class UiRow extends StatelessWidget {
  final String title;
  final String? subtitle;
  final Widget? trailing;
  const UiRow({super.key, required this.title, this.subtitle, this.trailing});
  @override
  Widget build(BuildContext context) => Container(
    constraints: const BoxConstraints(minHeight: 52),
    padding: const EdgeInsets.symmetric(vertical: 10),
    decoration: const BoxDecoration(
      border: Border(top: BorderSide(color: Palette.gray100)),
    ),
    child: Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: Palette.gray900,
                ),
              ),
              if (subtitle != null)
                Text(
                  subtitle!,
                  style: const TextStyle(fontSize: 13, color: Palette.gray600),
                ),
            ],
          ),
        ),
        ?trailing,
      ],
    ),
  );
}
