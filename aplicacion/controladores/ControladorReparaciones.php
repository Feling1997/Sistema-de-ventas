<?php

require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class ControladorReparaciones {
    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN", "VENDEDOR"])) {
                flash_error("No tenes permisos para Reparaciones.");
                redirigir("index.php?c=ventas&a=inicio");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function iniciar_servidor_python(): bool {
        $ok = false;
        $carpeta = realpath(__DIR__ . "/../../reparaciones_python");
        $launcher = $carpeta !== false ? $carpeta . DIRECTORY_SEPARATOR . "CONTROL REPARACIONES.exe" : "";
        if ($launcher !== "" && is_file($launcher)) {
            $comando = 'start "" /B "' . $launcher . '"';
            @pclose(@popen($comando, "r"));
            $ok = true;
        } else if ($carpeta !== false) {
            $pythonw = $carpeta . DIRECTORY_SEPARATOR . "python_runtime" . DIRECTORY_SEPARATOR . "pythonw.exe";
            $web_app = $carpeta . DIRECTORY_SEPARATOR . "web_app.py";
            if (is_file($pythonw) && is_file($web_app)) {
                $comando = 'start "" /B "' . $pythonw . '" "' . $web_app . '" --no-browser';
                @pclose(@popen($comando, "r"));
                $ok = true;
            }
        }
        return $ok;
    }

    public function index(): void {
        if ($this->permiso()) {
            $url = "http://127.0.0.1:8765/";
            $servidor_iniciado = $this->iniciar_servidor_python();
            $body_class = "bg-light page-reparaciones";
            $es_panel_aparte = false;
            $url_volver = "index.php?c=ventas&a=inicio";
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            ?>
            <div class="module-shell">
              <?php if (!$servidor_iniciado): ?>
                <div class="alert alert-warning mb-3">
                  No se encontro el iniciador de Reparaciones. Revisar la carpeta reparaciones_python de la instalacion.
                </div>
              <?php endif; ?>
              <div class="reparaciones-frame-wrap">
                <div class="reparaciones-loading" id="reparacionesLoading">Cargando Reparaciones...</div>
                <iframe id="reparacionesFrame" class="reparaciones-frame" title="Reparaciones"></iframe>
              </div>
            </div>
            <script>
              (function () {
                const frame = document.getElementById('reparacionesFrame');
                const loading = document.getElementById('reparacionesLoading');
                let accionPendiente = null;
                if (!frame)
                  return;
                function enviarVista(vista, estado) {
                  const mensaje = { tipo: 'reparaciones:vista', vista: vista, estado: estado || 'TODOS' };
                  if (!frame.contentWindow || !frame.src) {
                    accionPendiente = mensaje;
                    return;
                  }
                  frame.contentWindow.postMessage(mensaje, 'http://127.0.0.1:8765');
                }
                frame.addEventListener('load', function () {
                  if (loading)
                    loading.classList.add('d-none');
                  if (accionPendiente) {
                    frame.contentWindow.postMessage(accionPendiente, 'http://127.0.0.1:8765');
                    accionPendiente = null;
                  }
                });
                document.querySelectorAll('.js-reparaciones-nav').forEach(function (boton) {
                  boton.addEventListener('click', function () {
                    document.querySelectorAll('.js-reparaciones-nav').forEach(function (item) {
                      item.classList.remove('active');
                    });
                    boton.classList.add('active');
                    enviarVista(boton.dataset.reparacionesView || 'inicio', boton.dataset.reparacionesEstado || 'TODOS');
                  });
                });
                setTimeout(function () {
                  frame.src = <?= json_encode($url) ?>;
                }, 900);
              })();
            </script>
            <?php
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function abrir(): void {
        $this->index();
    }
}
