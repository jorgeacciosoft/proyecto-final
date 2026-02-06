import { Component, OnInit } from '@angular/core';
import { DataService } from '../../services/data.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-mis-reservas',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mis-reservas.component.html'
})
export class MisReservasComponent implements OnInit {
  reservas: any[] = [];
  paginaActual: number = 1;
  reservasPorPagina: number = 5;

  get totalPaginas(): number {
    return Math.ceil(this.reservas.length / this.reservasPorPagina);
  }

  get hayReservasActivas(): boolean {
    return this.reservas.some(r => r.estado === 'confirmada');
  }
  cambiarPagina(pagina: number) {
    if (pagina >= 1 && pagina <= this.totalPaginas) {
      this.paginaActual = pagina;
    }
  }

  constructor(private dataService: DataService) {}

  ngOnInit(): void {
    this.cargarReservas();
  }

  cargarReservas() {
    this.dataService.getMisReservas().subscribe(data => this.reservas = data);
  }

  cancelar(id: number) {
    Swal.fire({
      title: '¿Estás seguro de que deseas cancelar esta reserva?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, cancelar',
      cancelButtonText: 'No, mantener'
    }).then((result: any) => {
      if (result.isConfirmed) {
        this.dataService.cancelarReserva(id).subscribe({
          next: () => {
            Swal.fire({
              toast: true,
              position: 'bottom-end',
              icon: 'success',
              title: 'Reserva cancelada',
              showConfirmButton: false,
              timer: 2000
            });
            this.cargarReservas(); // Recargamos la lista tras borrar
          },
          error: () => {
            Swal.fire('Error', 'No se pudo cancelar la reserva.', 'error');
          }
        });
      }
    });
  }

  verInstalaciones(){
    // Navegamos a la página de instalaciones
    window.location.href = '/pistas';
  }


  esCancelable(fechaReserva: string): boolean {
  const fechaR = new Date(fechaReserva);
  const hoy = new Date();

  // Calculamos la diferencia en milisegundos
  const diferencia = fechaR.getTime() - hoy.getTime();

  // Convertimos a días (1000ms * 60s * 60m * 24h)
  const diasDiferencia = diferencia / (1000 * 60 * 60 * 24);

  // Si faltan más de 2 días (48 horas exactas), es cancelable
  return diasDiferencia >= 2;
}
}
