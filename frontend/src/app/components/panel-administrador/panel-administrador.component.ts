import { Component, OnInit } from '@angular/core';
import { DataService } from '../../services/data.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';
import { RouterLink } from "@angular/router";
import { environment } from '../../../environments/environments';



const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
});
@Component({
  selector: 'panel-administrador',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './panel-administrador.component.html',
  styleUrls: ['./panel-administrador.component.css']
})


export class PanelAdministradorComponent implements OnInit {
  todasReservas: any[] = [];
  listaUsuarios: any[] = [];
  paginaActual: number = 1;
  reservasPorPagina: number = 5;
  todasPistas: any[] = [];

  //Variable para la URL de las imágenes
public UPLOADS_URL = environment.uploadsUrl; // Aseguramos que se use la URL correcta para las imágenes

  get totalPaginas(): number {
    return Math.ceil(this.todasReservas.length / this.reservasPorPagina);
  }
  get hayReservasActivas(): boolean {
    return this.todasReservas.some(r => r.estado === 'confirmada');
  }
  cambiarPagina(pagina: number) {
    if (pagina >= 1 && pagina <= this.totalPaginas) {
      this.paginaActual = pagina;
    }
  }

  constructor(private dataService: DataService) {
    // Aseguramos que se use la URL correcta para las imágenes
    this.UPLOADS_URL = environment.uploadsUrl; // Aseguramos que se use la URL correcta para las imágenes
  }


  ngOnInit() {
  this.cargarTodo();
  this.cargarPistas();
}
cargarPistas() {
  this.dataService.getPistas().subscribe({
    next: (data) => {
      this.todasPistas = data;
    },
    error: (err) => console.error('Error al cargar pistas', err)
  });
}
// Variable para la pestaña activa
pestanaActiva: string = 'reservas';

// Función para cambiar de pestaña
cambiarPestana(nombre: string) {
  this.pestanaActiva = nombre;
  this.paginaActual = 1; // Reiniciamos a la primera página al cambiar de pestaña
}

cargarTodo() {
  this.dataService.getTodasReservas().subscribe(res => this.todasReservas = res);
  this.dataService.getUsuarios().subscribe(res => this.listaUsuarios = res);
  this.dataService.getPistas().subscribe(res => this.todasPistas = res);
}


eliminarUsuario(id: number) {
  Swal.fire({
    title: '¿Eliminar usuario?',
    text: "Esto borrará también todas sus reservas",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    confirmButtonColor: '#d33'
  }).then((result) => {
    if (result.isConfirmed) {
      this.dataService.borrarUsuario(id).subscribe(() => {
        Toast.fire({
          icon: 'success',
          title: 'Usuario borrado'
        });
        this.cargarTodo();
      });
    }
  });
}

modificarUsuario(id: number, nombre: string, email: string, rol: string) {
  Swal.fire({
    title: 'Editar Usuario',
    html: `
      <input id="swal-name" class="swal2-input" value="${nombre}" placeholder="Nombre">
      <input id="swal-email" class="swal2-input" value="${email}" placeholder="Email">
      <select id="swal-rol" class="swal2-select">
        <option value="user" ${rol === 'user' ? 'selected' : ''}>Usuario</option>
        <option value="admin" ${rol === 'admin' ? 'selected' : ''}>Admin</option>
      </select>
    `,
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Guardar Cambios',
    cancelButtonText: 'Cancelar',
    preConfirm: () => {
      return {
        id: id,
        nombre: (document.getElementById('swal-name') as HTMLInputElement).value,
        email: (document.getElementById('swal-email') as HTMLInputElement).value,
        rol: (document.getElementById('swal-rol') as HTMLSelectElement).value
      }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      this.dataService.actualizarUsuario(result.value).subscribe(() => {
        Toast.fire({
          icon: 'success',
          title: '¡Actualizado! Los datos se han guardado.'
        });
        this.cargarTodo();
      });
    }
  });
}

eliminarReservaAdmin(id: number) {
  Swal.fire({
    title: '¿Eliminar reserva?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    confirmButtonColor: '#d33'
  }).then((result) => {
    if (result.isConfirmed) {
      this.dataService.cancelarReserva(id).subscribe(() => {
        // Confirmación no intrusiva
        Toast.fire({
          icon: 'error',
          title: 'Reserva eliminada'
        });
        this.cargarTodo();
      });
    }
  });
}

  prepararNuevaInstalacion() {
    Swal.fire({
      title: 'Nueva Instalación',
      html: `
        <input id="swal-nombre" class="swal2-input" placeholder="Nombre de la pista">
        <input id="swal-tipo" class="swal2-input" placeholder="Tipo (e.g. tenis, pádel)">
        <input id="swal-precio" type="number" class="swal2-input" placeholder="Precio por hora">
        <input id="swal-descripcion" class="swal2-input" placeholder="Descripción">
          <label class="form-label small text-muted">Imagen de la instalación:</label>
          <input id="swal-file" type="file" class="form-control" accept="image/*">
        </input>
      `,
      showCancelButton: true,
      confirmButtonText: 'Crear',
      preConfirm: () => {
        const nombre = (document.getElementById('swal-nombre') as HTMLInputElement).value;
        const tipo = (document.getElementById('swal-tipo') as HTMLInputElement).value;
        const precio = (document.getElementById('swal-precio') as HTMLInputElement).value;
        const descripcion = (document.getElementById('swal-descripcion') as HTMLInputElement)?.value || '';
        const fileInput = document.getElementById('swal-file') as HTMLInputElement;
        const archivo = fileInput.files ? fileInput.files[0] : null;

        if (!nombre || !tipo || !precio || !descripcion || !archivo) {
          Swal.showValidationMessage('Todos los campos son obligatorios');
          return false;
        }

        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('tipo', tipo);
        formData.append('precio_hora', precio);
        formData.append('descripcion', descripcion);
        formData.append('imagen_url', archivo);
        return formData;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        this.dataService.crearInstalacion(result.value).subscribe({
          next: () => {
            Toast.fire({
              icon: 'success',
              title: 'Instalación creada',
            });
            this.cargarPistas();
          },
          error: () => Swal.fire('Error', 'No se pudo subir la imagen al servidor Docker', 'error')
        });
      }
    });
  }

  editarInstalacion(pista: any) { // El parámetro DEBE llamarse 'pista'
  Swal.fire({
    title: 'Editar Instalación',
    html: `
      <input id="swal-nombre" class="swal2-input" value="${pista.nombre}">
      <input id="swal-tipo" class="swal2-input" value="${pista.tipo}">
      <input id="swal-precio" type="number" class="swal2-input" value="${pista.precio_hora}">
      <input id="swal-descripcion" class="swal2-input" value="${pista.descripcion}">
      <label class="form-label small text-muted">Cambiar imagen (opcional):</label>
      <input id="swal-file" type="file" class="form-control" accept="image/*">
    `,
    showCancelButton: true,
    confirmButtonText: 'Guardar Cambios',
    preConfirm: () => {
      const fd = new FormData();
      // Accedemos a 'pista' que es el objeto que pasamos desde el HTML
      fd.append('id', pista.id);
      fd.append('nombre', (document.getElementById('swal-nombre') as HTMLInputElement).value);
      fd.append('tipo', (document.getElementById('swal-tipo') as HTMLInputElement).value);
      fd.append('precio_hora', (document.getElementById('swal-precio') as HTMLInputElement).value);
      fd.append('descripcion', (document.getElementById('swal-descripcion') as HTMLInputElement).value);

      // Enviamos el nombre de la imagen actual (de HeidiSQL) por si no se sube nada nuevo
      fd.append('imagen_actual', pista.imagen_url);

      const fileInput = document.getElementById('swal-file') as HTMLInputElement;
      if (fileInput.files?.[0]) {
        // Usamos la llave 'imagen_url' para que coincida con tu $_FILES en PHP
        fd.append('imagen_url', fileInput.files[0]);
      }
      return fd;
    }
  }).then((result) => {
    if (result.isConfirmed) {
      this.dataService.modificarInstalacion(result.value).subscribe({
        next: () => {
          Swal.fire('¡Actualizada!', 'Los cambios se han guardado.', 'success');
          this.cargarPistas(); // Refresca la vista para ver los cambios
        },
        error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
      });
    }
  });
}

  eliminarInstalacion(id: number) {
    Swal.fire({
      title: '¿Eliminar instalación?',
      text: "Esta acción es permanente y eliminará todas las reservas asociadas",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        this.dataService.eliminarInstalacion(id).subscribe(() => {
          Toast.fire({
            icon: 'error',
            title: 'Instalación eliminada'
          });
          this.cargarTodo();
        });
      }
    });
  }
}
