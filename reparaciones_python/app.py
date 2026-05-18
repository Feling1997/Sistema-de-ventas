from database import inicializar_base
from ui import ReparacionesApp


def main():
    inicializar_base()
    app = ReparacionesApp()
    app.mainloop()


if __name__ == "__main__":
    main()
