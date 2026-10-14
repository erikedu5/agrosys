export function printProvisionalTicket(command, result, ticketContext = {}) {
    const context = typeof ticketContext === 'string' ? { branchName: ticketContext } : ticketContext;
    const items = command.items ?? [];
    const rows = items.map(item => `
        <tr><td colspan="2" class="small">${escapeHtml(item.name)}</td></tr>
        <tr><td class="small">${formatQuantity(item.quantity)} x $ ${money(item.unitPrice ?? Number(item.total) / Math.max(Number(item.quantity), 1))}</td><td class="right small">$ ${money(item.total)}</td></tr>
    `).join('');
    const popup = window.open('', '_blank', 'width=420,height=720');
    if (!popup) return false;

    const commonTicket = (sellerCopy) => `
        <section class="ticket ${sellerCopy ? 'seller-copy' : ''}">
            ${sellerCopy ? '<div class="copy-label">COPIA DEL VENDEDOR</div>' : ''}
            <div class="center">
                <div class="bold">${escapeHtml(context.companyName ?? 'AgroSys')}</div>
                ${context.address ? `<div class="small">${escapeHtml(context.address)}</div>` : ''}
                ${context.phone ? `<div class="small">Tel: ${escapeHtml(context.phone)}</div>` : ''}
                ${context.rfc ? `<div class="small">RFC: ${escapeHtml(context.rfc)}</div>` : ''}
            </div>
            <hr>
            <div class="small">Fecha: ${formatDate(command.occurredAt)}</div>
            <div class="small">Folio: ${escapeHtml(result.localFolio)}</div>
            <div class="small">Tipo: ${escapeHtml(command.saleType ?? 'Contado')}</div>
            <div class="small">Cliente: <span class="bold">${escapeHtml(command.customerName ?? 'Público general')}</span></div>
            <div class="small">Vendedor: ${escapeHtml(command.sellerName ?? '')}</div>
            <hr>
            <div class="bold small">Productos</div>
            <table>${rows}</table>
            <hr>
            <table><tr class="bold"><td>Total</td><td class="right">$ ${money(command.total)}</td></tr></table>
            <hr>
            <div class="center small">${escapeHtml(context.notice ?? 'Gracias por su compra')}</div>
            ${sellerCopy ? `<div class="pending"><strong>PENDIENTE DE SINCRONIZACIÓN</strong><br>Conservar hasta confirmar en AgroSys<br><span class="tiny">Operación: ${escapeHtml(command.operationId)}<br>Dispositivo: ${escapeHtml(command.deviceId)}</span></div>` : ''}
            <br><div class="cut"></div>
        </section>`;

    popup.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Ticket ${escapeHtml(result.localFolio)}</title><style>
        @page{margin:4px;padding:10px}body{font-family:Consolas,"Courier New",monospace;font-size:14px;width:80mm;margin:0 auto}.ticket{padding:8px}.seller-copy{break-before:page;page-break-before:always}.center{text-align:center}.right{text-align:right}.bold{font-weight:bold}.small{font-size:11px}.tiny{font-size:9px;word-break:break-all}hr{border:0;border-top:1px dashed #000;margin:4px 0}table{width:100%;border-collapse:collapse}td{padding:4px 0;vertical-align:top}.cut{width:100%;border-bottom:1px dotted #000}.copy-label{text-align:center;border:2px solid #000;padding:5px;margin-bottom:6px;font-weight:bold}.pending{text-align:center;border:2px dashed #000;padding:7px;margin-top:10px;font-size:11px}
    </style></head><body>${commonTicket(false)}${commonTicket(true)}<script>window.onload=()=>setTimeout(()=>window.print(),300)<\/script></body></html>`);
    popup.document.close();
    return true;
}

const money = value => Number(value ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQuantity = value => Number(value ?? 0).toLocaleString('es-MX', { maximumFractionDigits: 2 });
const formatDate = value => new Date(value).toLocaleString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
