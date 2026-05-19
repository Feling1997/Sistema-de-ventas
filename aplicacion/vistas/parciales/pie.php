</div>
<script>
(function () {
  function inicializarDataTables(root) {
    if (typeof DataTable === 'undefined')
      return;
    const tablas = [
      '#tablaClientes',
      '#tablaUsuarios',
      '#tablaProductos',
      '#tablaStockProductos',
      '#tablaStock'
    ];
    tablas.forEach(function (selector) {
      const tabla = root.querySelector(selector);
      if (!tabla)
        return;
      if (tabla.dataset.dtReady === '1' || tabla.classList.contains('dataTable'))
        return;
      new DataTable(selector, {
        searching: false,
        language: {
          search: 'Buscar:',
          lengthMenu: 'Mostrar _MENU_',
          info: 'Mostrando _START_ a _END_ de _TOTAL_',
          infoEmpty: 'Sin datos',
          zeroRecords: 'No se encontraron resultados',
          paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
        }
      });
      tabla.dataset.dtReady = '1';
    });
  }

  function inicializarVentasDetalle(root) {
    const contenedor = root.querySelector('#ventasResultados');
    if (!contenedor || contenedor.dataset.detalleVentasReady === '1')
      return;
    const datosEl = contenedor.querySelector('#ventasDetalleDatos');
    const detalle = contenedor.querySelector('#ventasDetalle');
    const tabla = contenedor.querySelector('.ventas-select-table');
    if (!datosEl || !detalle || !tabla)
      return;

    let datos = {};
    try {
      datos = JSON.parse(datosEl.textContent || '{}');
    } catch (e) {
      datos = {};
    }

    function textoVenta(venta) {
      const items = Array.isArray(venta.items) ? venta.items : [];
      const lineasItems = items.length > 0
        ? items.map(function (item) {
            return '- ' + (item.producto || '') + '\n  Cantidad: ' + item.cantidad + ' | Precio: ' + item.precio + ' | Desc: ' + item.descuento + '% | Subtotal: ' + item.subtotal;
          }).join('\n')
        : 'Sin items.';
      const fiscalExtra = venta.fiscal_extra ? '\nFiscal detalle: ' + venta.fiscal_extra : '';
      return 'Ticket: #' + venta.id + '\n' +
        'Fecha: ' + (venta.fecha || '') + '\n' +
        'Cliente: ' + (venta.cliente || '') + '\n' +
        'Vendedor: ' + (venta.vendedor || '') + '\n\n' +
        'Fiscal: ' + (venta.fiscal || '') + fiscalExtra + '\n' +
        'Total: ' + (venta.total || '') + '\n\n' +
        'Detalle:\n' + lineasItems;
    }

    tabla.addEventListener('click', function (e) {
      if (e.target.closest('a'))
        return;
      const fila = e.target.closest('tr[data-venta-id]');
      if (!fila)
        return;
      const id = fila.getAttribute('data-venta-id');
      const venta = datos[id];
      tabla.querySelectorAll('tr.sel').forEach(function (tr) {
        tr.classList.remove('sel');
      });
      fila.classList.add('sel');
      detalle.textContent = venta ? textoVenta(venta) : 'No se encontro el detalle de esta venta.';
    });

    contenedor.dataset.detalleVentasReady = '1';
  }

  function construirUrl(form) {
    const action = form.getAttribute('action') || window.location.pathname;
    const method = (form.getAttribute('method') || 'get').toLowerCase();
    const data = new FormData(form);
    const params = new URLSearchParams();
    data.forEach(function (value, key) {
      params.append(key, value);
    });
    if (method === 'get')
      return action + (action.indexOf('?') === -1 ? '?' : '&') + params.toString();
    return action;
  }

  function actualizarContenido(form, url) {
    const selectorObjetivo = form.getAttribute('data-search-target') || '';
    const objetivoActual = selectorObjetivo ? document.querySelector(selectorObjetivo) : null;
    const contenedorActual = document.querySelector('.container.py-5');
    if (!contenedorActual)
      return;
    fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        if (selectorObjetivo && objetivoActual) {
          const objetivoNuevo = doc.querySelector(selectorObjetivo);
          if (!objetivoNuevo)
            return;
          objetivoActual.outerHTML = objetivoNuevo.outerHTML;
        } else {
          const contenedorNuevo = doc.querySelector('.container.py-5');
          if (!contenedorNuevo)
            return;
          contenedorActual.innerHTML = contenedorNuevo.innerHTML;
        }
        window.history.replaceState({}, '', url);
        enlazarBusquedas(document);
        enlazarExportaciones(document);
        inicializarDataTables(document);
        inicializarVentasDetalle(document);
      })
      .catch(function () {
        window.location.href = url;
      });
  }

  function enlazarBusquedas(root) {
    const forms = root.querySelectorAll('form[data-auto-submit-search="true"]');
    forms.forEach(function (form) {
      if (form.dataset.searchBound === '1')
        return;
      form.dataset.searchBound = '1';
      const inputBuscar = form.querySelector('input[name="buscar"]');
      const inputDesde = form.querySelector('input[name="fecha_desde"]');
      const inputHasta = form.querySelector('input[name="fecha_hasta"]');
      const selectCampo = form.querySelector('select[name="campo"]');
      const selectMetodo = form.querySelector('select[name="metodo"]');
      const selects = form.querySelectorAll('select');
      let timer = null;

      function enviarConDemora() {
        if (timer)
          clearTimeout(timer);
        timer = setTimeout(function () {
          actualizarContenido(form, construirUrl(form));
        }, 250);
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        actualizarContenido(form, construirUrl(form));
      });
      if (inputBuscar)
        inputBuscar.addEventListener('input', enviarConDemora);
      if (inputDesde)
        inputDesde.addEventListener('change', enviarConDemora);
      if (inputHasta)
        inputHasta.addEventListener('change', enviarConDemora);
      selects.forEach(function (select) {
        select.addEventListener('change', enviarConDemora);
      });
    });
  }

  function nombreArchivoExportacion(form, formato) {
    const reporte = form.querySelector('[name="reporte"]');
    const partes = [];
    if (reporte && reporte.selectedOptions.length)
      partes.push(reporte.selectedOptions[0].textContent || '');
    const lista = Array.from(form.querySelectorAll('[name="id_lista_precio"]')).find(function (select) {
      return !select.disabled && select.offsetParent !== null;
    });
    if (lista && lista.selectedOptions.length && lista.value !== '0')
      partes.push(lista.selectedOptions[0].textContent || '');
    const stock = form.querySelector('[name="id_stock"]:not(:disabled)');
    if (stock && stock.selectedOptions.length)
      partes.push(stock.selectedOptions[0].textContent || '');
    const baseTexto = partes.join(' ') || form.dataset.exportName || 'exportacion';
    const base = baseTexto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-zA-Z0-9_-]+/g, '_').replace(/^_+|_+$/g, '').toLowerCase() || 'exportacion';
    const fecha = new Date();
    const pad = function (n) { return String(n).padStart(2, '0'); };
    const marca = fecha.getFullYear() + pad(fecha.getMonth() + 1) + pad(fecha.getDate()) + '_' + pad(fecha.getHours()) + pad(fecha.getMinutes());
    const ext = formato === 'pdf' ? 'pdf' : 'csv';
    return base + '_' + marca + '.' + ext;
  }

  function nombreDesdeContentDisposition(response) {
    const header = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || '';
    const utf = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf)
      return decodeURIComponent(utf[1].replace(/["']/g, ''));
    const normal = header.match(/filename="?([^";]+)"?/i);
    return normal ? normal[1] : '';
  }

  function tipoMimeArchivo(nombre, tipoRespuesta) {
    const ext = String(nombre || '').split('.').pop().toLowerCase();
    if (ext === 'pdf')
      return 'application/pdf';
    if (ext === 'csv')
      return 'text/csv';
    return tipoRespuesta || 'application/octet-stream';
  }

  function abrirArchivoGenerado(blob, nombre) {
    const ext = String(nombre || '').split('.').pop().toLowerCase();
    const url = URL.createObjectURL(blob);
    const ventana = window.open(url, '_blank');
    if (ventana)
      setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
  }

  function enlazarExportaciones(root) {
    const forms = root.querySelectorAll('form[data-save-picker="true"]');
    forms.forEach(function (form) {
      if (form.dataset.savePickerBound === '1')
        return;
      form.dataset.savePickerBound = '1';
      form.addEventListener('submit', async function (e) {
        const formatoEl = form.querySelector('[name="formato"]');
        const formato = formatoEl ? String(formatoEl.value || '').toLowerCase() : '';
        if (formato === 'html') {
          e.preventDefault();
          window.open(construirUrl(form), '_blank');
          return;
        }
        if (typeof window.showSaveFilePicker !== 'function')
          return;
        e.preventDefault();
        const fallback = nombreArchivoExportacion(form, formato);
        let response = null;
        let blob = null;
        try {
          response = await fetch(construirUrl(form), { credentials: 'same-origin' });
          if (!response.ok)
            throw new Error('No se pudo generar la exportacion.');
          blob = await response.blob();
        } catch (err) {
          alert(err && err.message ? err.message : 'No se pudo generar el archivo.');
          return;
        }
        const nombre = nombreDesdeContentDisposition(response) || fallback;
        const extension = nombre.split('.').pop().toLowerCase() || 'csv';
        const mime = tipoMimeArchivo(nombre, blob.type);
        let handle = null;
        try {
          handle = await window.showSaveFilePicker({
            suggestedName: nombre,
            types: [{
              description: 'Archivo de exportacion',
              accept: { [mime]: ['.' + extension] }
            }]
          });
        } catch (err) {
          return;
        }
        try {
          const writable = await handle.createWritable();
          await writable.write(blob);
          await writable.close();
          abrirArchivoGenerado(blob, nombre);
        } catch (err) {
          alert(err && err.message ? err.message : 'No se pudo guardar el archivo.');
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    enlazarBusquedas(document);
    enlazarExportaciones(document);
    inicializarDataTables(document);
    inicializarVentasDetalle(document);
  });

  document.addEventListener('keydown', function (e) {
    const atajo = (document.body.dataset.atajoReparaciones || '').toUpperCase();
    const url = document.body.dataset.urlReparaciones || '';
    if (!atajo || !url)
      return;
    if ((e.key || '').toUpperCase() === atajo) {
      e.preventDefault();
      window.location.href = url;
    }
  });
})();
</script>
</body>
</html>
