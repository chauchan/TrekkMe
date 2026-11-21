import 'package:flutter/material.dart';

import '../../core/rut.dart';
import '../../models/excursion.dart';

/// Pide los datos mínimos de un acompañante que no tiene cuenta en la app.
/// Devuelve el [Acompanante] o null si se cancela.
Future<Acompanante?> pedirAcompanante(BuildContext context) =>
    showDialog<Acompanante>(
      context: context,
      builder: (_) => const _AcompananteDialog(),
    );

class _AcompananteDialog extends StatefulWidget {
  const _AcompananteDialog();

  @override
  State<_AcompananteDialog> createState() => _AcompananteDialogState();
}

class _AcompananteDialogState extends State<_AcompananteDialog> {
  final _formKey = GlobalKey<FormState>();
  final _nombre = TextEditingController();
  final _rut = TextEditingController();
  final _edad = TextEditingController();
  final _condiciones = TextEditingController();

  @override
  void dispose() {
    _nombre.dispose();
    _rut.dispose();
    _edad.dispose();
    _condiciones.dispose();
    super.dispose();
  }

  void _agregar() {
    if (!_formKey.currentState!.validate()) return;
    final condiciones = _condiciones.text.trim();
    Navigator.pop(
      context,
      Acompanante(
        nombre: _nombre.text.trim(),
        rut: Rut.formatear(_rut.text),
        edad: int.parse(_edad.text.trim()),
        condicionesMedicas: condiciones.isEmpty ? 'Ninguna' : condiciones,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Agregar acompañante'),
      content: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: _nombre,
                textCapitalization: TextCapitalization.words,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(labelText: 'Nombre completo'),
                validator: (v) => (v == null || v.trim().isEmpty)
                    ? 'Ingresa el nombre.'
                    : null,
              ),
              TextFormField(
                controller: _rut,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'RUT',
                  hintText: '12.345.678-5',
                ),
                validator: (v) => Rut.esValido(v ?? '') ? null : 'RUT no válido.',
              ),
              TextFormField(
                controller: _edad,
                keyboardType: TextInputType.number,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(labelText: 'Edad'),
                validator: (v) {
                  final edad = int.tryParse(v?.trim() ?? '');
                  return (edad == null || edad < 0 || edad > 120)
                      ? 'Edad entre 0 y 120.'
                      : null;
                },
              ),
              TextFormField(
                controller: _condiciones,
                textInputAction: TextInputAction.done,
                decoration: const InputDecoration(
                  labelText: 'Condición médica (opcional)',
                  hintText: 'Ninguna',
                ),
                onFieldSubmitted: (_) => _agregar(),
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancelar'),
        ),
        FilledButton(onPressed: _agregar, child: const Text('Agregar')),
      ],
    );
  }
}
