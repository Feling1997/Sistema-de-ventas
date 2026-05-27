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
        ordering: false,
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
        enlazarBackupDirectorio(document);
        actualizarEtiquetaDirectorioBackup();
        enlazarBackupPc(document);
        inicializarDataTables(document);
        inicializarVentasDetalle(document);
        enlazarOrdenamiento(document);
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

  function nombreArchivoBackup() {
    const fecha = new Date();
    const pad = function (n) { return String(n).padStart(2, '0'); };
    return 'respaldo_ventas_reparaciones_' +
      fecha.getFullYear() + pad(fecha.getMonth() + 1) + pad(fecha.getDate()) + '_' +
      pad(fecha.getHours()) + pad(fecha.getMinutes()) + pad(fecha.getSeconds()) + '.tar.gz';
  }

  function backupDb() {
    return new Promise(function (resolve, reject) {
      const req = indexedDB.open('ventas-backup-local', 1);
      req.onupgradeneeded = function () {
        req.result.createObjectStore('handles');
      };
      req.onsuccess = function () { resolve(req.result); };
      req.onerror = function () { reject(req.error); };
    });
  }

  async function backupGuardarHandle(handle) {
    const db = await backupDb();
    return new Promise(function (resolve, reject) {
      const tx = db.transaction('handles', 'readwrite');
      tx.objectStore('handles').put(handle, 'directorio');
      tx.oncomplete = function () { resolve(); };
      tx.onerror = function () { reject(tx.error); };
    });
  }

  async function backupObtenerHandle() {
    if (!window.indexedDB)
      return null;
    const db = await backupDb();
    return new Promise(function (resolve) {
      const tx = db.transaction('handles', 'readonly');
      const req = tx.objectStore('handles').get('directorio');
      req.onsuccess = function () { resolve(req.result || null); };
      req.onerror = function () { resolve(null); };
    });
  }

  async function backupTienePermiso(handle) {
    if (!handle)
      return false;
    const opts = { mode: 'readwrite' };
    if ((await handle.queryPermission(opts)) === 'granted')
      return true;
    return (await handle.requestPermission(opts)) === 'granted';
  }

  async function descargarBackupBlob() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';
    if (!csrf)
      throw new Error('No se encontro el token de seguridad. Recarga la pagina.');
    const response = await fetch('index.php?c=configuracion&a=descargar_respaldo_pc', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'csrf=' + encodeURIComponent(csrf)
    });
    if (!response.ok) {
      const texto = await response.text();
      throw new Error(texto || 'No se pudo generar el backup.');
    }
    return response.blob();
  }

  async function guardarBackupEnDirectorio(handle) {
    if (!await backupTienePermiso(handle))
      throw new Error('No hay permiso para escribir en la carpeta elegida.');
    const nombre = nombreArchivoBackup();
    const blob = await descargarBackupBlob();
    const fileHandle = await handle.getFileHandle(nombre, { create: true });
    const writable = await fileHandle.createWritable();
    await writable.write(blob);
    await writable.close();
    return nombre;
  }

  function actualizarEtiquetaDirectorioBackup() {
    const nombre = localStorage.getItem('backup-directorio-nombre') || '';
    document.querySelectorAll('[data-backup-directory-label="true"]').forEach(function (label) {
      if (nombre)
        label.textContent = 'Carpeta elegida en este navegador: ' + nombre + '. La ruta completa no se muestra por seguridad del navegador.';
    });
  }

  function enlazarBackupDirectorio(root) {
    root.querySelectorAll('[data-backup-directory-picker="true"]').forEach(function (boton) {
      if (boton.dataset.backupDirBound === '1')
        return;
      boton.dataset.backupDirBound = '1';
      boton.addEventListener('click', async function () {
        if (typeof window.showDirectoryPicker !== 'function') {
          alert('Tu navegador no permite examinar carpetas. Usa Chrome/Edge actualizado o escribi la ruta fija, por ejemplo E:\\Respaldos.');
          return;
        }
        try {
          const handle = await window.showDirectoryPicker({ mode: 'readwrite' });
          if (!await backupTienePermiso(handle))
            throw new Error('No hay permiso para escribir en la carpeta.');
          await backupGuardarHandle(handle);
          localStorage.setItem('backup-directorio-nombre', handle.name || 'carpeta elegida');
          actualizarEtiquetaDirectorioBackup();
          alert('Carpeta de backup automatico seleccionada correctamente.');
        } catch (err) {
          if (err && err.name === 'AbortError')
            return;
          alert(err && err.message ? err.message : 'No se pudo seleccionar la carpeta.');
        }
      });
    });
  }

  function enlazarBackupPc(root) {
    root.querySelectorAll('[data-backup-save-picker="true"]').forEach(function (boton) {
      if (boton.dataset.backupPcBound === '1')
        return;
      boton.dataset.backupPcBound = '1';
      boton.addEventListener('click', async function () {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';
        if (!csrf) {
          alert('No se encontro el token de seguridad. Recarga la pagina.');
          return;
        }
        if (typeof window.showSaveFilePicker !== 'function') {
          alert('Tu navegador no permite examinar carpetas desde el sistema. Escribi la ruta en el campo, por ejemplo E:\\Respaldos, o usa Chrome/Edge actualizado.');
          return;
        }
        const nombre = nombreArchivoBackup();
        let handle = null;
        try {
          handle = await window.showSaveFilePicker({
            suggestedName: nombre,
            types: [{
              description: 'Respaldo del sistema',
              accept: { 'application/gzip': ['.gz'] }
            }]
          });
        } catch (err) {
          return;
        }
        boton.disabled = true;
        const textoOriginal = boton.innerHTML;
        boton.innerHTML = '<span class="bi bi-clock-history"></span> Generando backup...';
        try {
          const blob = await descargarBackupBlob();
          const writable = await handle.createWritable();
          await writable.write(blob);
          await writable.close();
          alert('Backup guardado correctamente.');
        } catch (err) {
          alert(err && err.message ? err.message : 'No se pudo guardar el backup.');
        } finally {
          boton.disabled = false;
          boton.innerHTML = textoOriginal;
        }
      });
    });
  }

  function scopeOrdenamiento() {
    const params = new URLSearchParams(window.location.search);
    return 'orden:' + (params.get('c') || 'ventas') + ':' + (params.get('a') || 'index');
  }

  function aplicarOrdenGuardado() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('orden') || !params.has('c'))
      return;
    const guardado = sessionStorage.getItem(scopeOrdenamiento());
    if (!guardado)
      return;
    try {
      const datos = JSON.parse(guardado);
      if (!datos || !datos.orden || !datos.direccion)
        return;
      params.set('orden', datos.orden);
      params.set('direccion', datos.direccion);
      window.location.replace(window.location.pathname + '?' + params.toString());
    } catch (e) {
      sessionStorage.removeItem(scopeOrdenamiento());
    }
  }

  function enlazarOrdenamiento(root) {
    root.querySelectorAll('.js-sort-link').forEach(function (link) {
      if (link.dataset.sortBound === '1')
        return;
      link.dataset.sortBound = '1';
      link.addEventListener('click', function () {
        const direccion = link.dataset.sortDirection || '';
        if (!direccion)
          sessionStorage.removeItem(scopeOrdenamiento());
        else
          sessionStorage.setItem(scopeOrdenamiento(), JSON.stringify({ orden: link.dataset.sortKey || '', direccion: direccion }));
      });
    });
  }

  function mostrarAvisoBackup(mensaje) {
    alert(mensaje);
  }

  function ejecutarBackupAutomatico() {
    const body = document.body;
    if (!body || body.dataset.backupAutomatico !== '1')
      return;
    if ((body.dataset.backupFrecuencia || 'diario') === 'manual')
      return;
    const hora = body.dataset.backupHora || '';
    const partes = hora.split(':');
    if (partes.length < 2)
      return;
    const hh = Number(partes[0]);
    const mm = Number(partes[1]);
    if (!Number.isInteger(hh) || !Number.isInteger(mm))
      return;
    const ahora = new Date();
    const objetivo = new Date();
    objetivo.setHours(hh, mm, 0, 0);
    const avisoMinutos = Math.max(0, Math.min(180, parseInt(body.dataset.backupAvisoMinutos || '5', 10) || 0));
    const desde = new Date(objetivo.getTime() - avisoMinutos * 60000);
    const hasta = new Date(objetivo.getTime() + 60 * 60000);
    if (ahora < desde || ahora > hasta)
      return;
    const fecha = String(ahora.getFullYear()) + String(ahora.getMonth() + 1).padStart(2, '0') + String(ahora.getDate()).padStart(2, '0');
    const clave = 'backup-auto-' + fecha + '-' + hora;
    const estadoGuardado = localStorage.getItem(clave) || '';
    if (estadoGuardado === 'ok' || estadoGuardado === 'error-mostrado' || sessionStorage.getItem(clave) === 'procesando')
      return;
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';
    if (!csrf)
      return;
    const ejecutar = sessionStorage.getItem(clave + '-confirmado') === '1' || confirm('Se va a realizar el backup automatico configurado. Ejecutar ahora?');
    if (!ejecutar)
      return;
    sessionStorage.setItem(clave + '-confirmado', '1');
    sessionStorage.setItem(clave, 'procesando');
    (async function () {
      let localNavegadorOk = false;
      if (body.dataset.backupAutoLocal === '1') {
        const handle = await backupObtenerHandle();
        if (handle) {
          await guardarBackupEnDirectorio(handle);
          localNavegadorOk = true;
        }
      }
      const omitirLocal = localNavegadorOk ? '1' : '0';
      if (body.dataset.backupAutoBackblaze !== '1' && localNavegadorOk)
        return { ok: true, mensaje: 'Backup automatico guardado en la carpeta elegida.' };
      const response = await fetch('index.php?c=configuracion&a=ejecutar_respaldo_programado', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
        body: 'csrf=' + encodeURIComponent(csrf) + '&omitir_local_navegador=' + encodeURIComponent(omitirLocal)
      });
      return response.json();
    })()
      .then(function (data) {
        sessionStorage.removeItem(clave);
        if (data && data.ok) {
          localStorage.setItem(clave, 'ok');
          return;
        }
        localStorage.setItem(clave, 'error-mostrado');
        mostrarAvisoBackup((data && data.mensaje) ? data.mensaje : 'No se pudo realizar el backup automatico.');
      })
      .catch(function () {
        sessionStorage.removeItem(clave);
        localStorage.setItem(clave, 'error-mostrado');
        mostrarAvisoBackup('No se pudo realizar el backup automatico. Revisa la conexion, Backblaze o la unidad externa.');
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    aplicarOrdenGuardado();
    enlazarBusquedas(document);
    enlazarExportaciones(document);
    enlazarBackupDirectorio(document);
    actualizarEtiquetaDirectorioBackup();
    enlazarBackupPc(document);
    inicializarDataTables(document);
    inicializarVentasDetalle(document);
    enlazarOrdenamiento(document);
    ejecutarBackupAutomatico();
    setInterval(ejecutarBackupAutomatico, 60000);
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
