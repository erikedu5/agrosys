<?php

namespace App\Services;

use App\Exceptions\FacturapiException;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\Venta;
use Facturapi\Facturapi;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacturapiService
{
    private Facturapi $client;
    private Empresa $empresa;

    public function __construct(Empresa $empresa)
    {
        $apiKey = $empresa->facturapi_api_key ?: config('services.facturapi.key');

        if (!$apiKey) {
            throw new FacturapiException('No hay una llave de Facturapi configurada para la empresa seleccionada.');
        }

        $this->client = new Facturapi($apiKey);
        $this->empresa = $empresa;
    }

    public static function make(?Empresa $empresa): ?self
    {
        if (!$empresa) {
            return null;
        }

        try {
            return new self($empresa);
        } catch (FacturapiException $exception) {
            Log::warning('FacturapiService: no se pudo instanciar el cliente', [
                'empresa' => $empresa->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    public function ensureCustomer(Clientes $cliente): string
    {
        if (!$cliente->requiereFactura) {
            throw new FacturapiException('El cliente no tiene habilitada la facturación.');
        }

        if (!$cliente->rfc) {
            throw new FacturapiException('El cliente no tiene RFC configurado.');
        }

        if (!$cliente->regimen_fiscal) {
            throw new FacturapiException('El cliente no tiene régimen fiscal configurado.');
        }

        if (!$cliente->codigo_postal) {
            throw new FacturapiException('El cliente no tiene código postal configurado.');
        }

        $data = [
            'legal_name' => $cliente->nombre,
            'tax_id' => strtoupper($cliente->rfc),
            'tax_system' => $cliente->regimen_fiscal,
            'address' => [
                'zip' => $cliente->codigo_postal,
            ],
        ];

        if ($cliente->email_facturacion) {
            $data['email'] = $cliente->email_facturacion;
        }

        try {
            if ($cliente->facturapi_customer_id) {
                $customer = $this->client->Customers->update($cliente->facturapi_customer_id, $data);
            } else {
                $customer = $this->client->Customers->create($data);
                $cliente->facturapi_customer_id = $customer->id;
                $cliente->save();
            }

            return $cliente->facturapi_customer_id ?? $customer->id;
        } catch (Throwable $throwable) {
            Log::error('Error al sincronizar cliente en Facturapi', [
                'cliente' => $cliente->id,
                'error' => $throwable->getMessage(),
            ]);
            throw new FacturapiException('No fue posible sincronizar al cliente con Facturapi.', 0, $throwable);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildInvoiceItems(Venta $venta): array
    {
        $defaultProductKey = config('services.facturapi.default_product_key', '01010101');
        $defaultUnitKey = config('services.facturapi.default_unit_key', 'ACT');

        return ProductoVenta::with(['producto.marca'])
            ->where('id_venta', $venta->id)
            ->get()
            ->map(function (ProductoVenta $item) use ($defaultProductKey, $defaultUnitKey) {
                $producto = Producto::find($item->id_producto);
                $nombre = trim($producto?->nombre ?? '');
                $tamano = trim($producto?->tamano ?? '');
                $marca = trim($producto?->marca?->nombre ?? '');

                $partesDescripcion = array_filter([$nombre, $tamano, $marca], static fn ($valor) => $valor !== '');
                $description = $partesDescripcion ? implode(' - ', $partesDescripcion) : "Producto {$item->id_producto}";
                $productKey = $defaultProductKey;
                $unitKey = $defaultUnitKey;

                $price = 0.0;
                if ($item->cantidad > 0) {
                    $price = round((float) $item->total_productos / (float) $item->cantidad, 2);
                }

                $productData = [
                    'quantity' => (float) $item->cantidad,
                    'product' => [
                        'description' => $description,
                        'product_key' => $productKey,
                        'unit_key' => $unitKey,
                        'price' => $price,
                        'tax_included' => true,
                    ],
                ];

                if (!empty($producto?->barcode)) {
                    $productData['product']['sku'] = $producto->barcode;
                }

                return $productData;
            })
            ->toArray();
    }

    public function createInvoice(Venta $venta, Clientes $cliente): array
    {
        $customerId = $this->ensureCustomer($cliente);

        if (!$cliente->uso_cfdi) {
            throw new FacturapiException('El cliente no tiene uso CFDI configurado.');
        }

        $items = $this->buildInvoiceItems($venta);

        if (empty($items)) {
            throw new FacturapiException('No hay partidas para generar la factura.');
        }

        $paymentForm = config(
            $venta->tipo_venta === 'Contado'
                ? 'services.facturapi.default_payment_form_contado'
                : 'services.facturapi.default_payment_form_credito'
        );
        $paymentMethod = config(
            $venta->tipo_venta === 'Contado'
                ? 'services.facturapi.default_payment_method_contado'
                : 'services.facturapi.default_payment_method_credito'
        );

        $shouldSendEmail = !empty($cliente->email_facturacion);
        $payload = [
            'customer' => $customerId,
            'items' => $items,
            'use' => $cliente->uso_cfdi ?: config('services.facturapi.default_use_cfdi', 'G03'),
            'payment_form' => $paymentForm ?: '01',
            'payment_method' => $paymentMethod ?: 'PUE',
            'currency' => 'MXN',
        ];

        Log::info('Facturapi: iniciando creación de factura', [
            'venta' => $venta->id,
            'cliente' => $cliente->id,
            'empresa' => $this->empresa->id ?? null,
            'items' => count($items),
            'use' => $payload['use'],
            'payment_form' => $payload['payment_form'],
            'payment_method' => $payload['payment_method'],
            'enviar_correo' => $shouldSendEmail,
        ]);

        try {
            $invoice = $this->client->Invoices->create($payload);
        } catch (Throwable $throwable) {
            Log::error('Error al crear la factura en Facturapi', [
                'venta' => $venta->id,
                'cliente' => $cliente->id,
                'payload' => $payload,
                'error' => $throwable->getMessage(),
            ]);

            throw new FacturapiException('No se pudo crear la factura en Facturapi.', 0, $throwable);
        }

        Log::info('Facturapi: factura generada correctamente', [
            'venta' => $venta->id,
            'cliente' => $cliente->id,
            'facturapi_invoice_id' => $invoice->id ?? null,
            'status' => $invoice->status ?? null,
            'uuid' => $invoice->uuid ?? null,
        ]);

        if ($shouldSendEmail && !empty($invoice->id)) {
            try {
                $this->client->Invoices->send_by_email($invoice->id, $cliente->email_facturacion);
                Log::info('Facturapi: correo de factura enviado', [
                    'venta' => $venta->id,
                    'cliente' => $cliente->id,
                    'facturapi_invoice_id' => $invoice->id,
                    'email' => $cliente->email_facturacion,
                ]);
            } catch (Throwable $mailException) {
                Log::warning('Facturapi: no se pudo enviar el correo de la factura', [
                    'venta' => $venta->id,
                    'cliente' => $cliente->id,
                    'facturapi_invoice_id' => $invoice->id,
                    'email' => $cliente->email_facturacion,
                    'error' => $mailException->getMessage(),
                ]);
            }
        }

        return [
            'id' => $invoice->id ?? null,
            'status' => $invoice->status ?? null,
            'uuid' => $invoice->uuid ?? null,
            'pdf_url' => $invoice->files->pdf ?? null,
            'xml_url' => $invoice->files->xml ?? null,
        ];
    }
}
