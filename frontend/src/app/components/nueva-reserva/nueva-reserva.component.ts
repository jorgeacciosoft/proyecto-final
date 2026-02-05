import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { DataService } from '../../services/data.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-nueva-reserva',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './nueva-reserva.component.html'
})
export class NuevaReservaComponent implements OnInit {
  reservaForm!: FormGroup;
  pistaId!: number;
  mensaje: string = '';

  constructor(
    private route: ActivatedRoute,
    private fb: FormBuilder,
    private dataService: DataService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.pistaId = Number(this.route.snapshot.paramMap.get('id'));

    this.reservaForm = this.fb.group({
      instalacion_id: [this.pistaId, Validators.required],
      fecha: ['', Validators.required],
      hora: ['', [Validators.required, Validators.pattern("^([01]?[0-9]|2[0-3]):00$")]] // Solo horas en punto
    });
  }

    confirmarReserva() {
  if (this.reservaForm.valid) {
    this.dataService.reservar(this.reservaForm.value).subscribe({
      next: (res) => {
        if (res.status === 'success') {
          Swal.fire({
            title: '¡Reserva confirmada!',
            text: 'Tu pista ha sido reservada correctamente.',
            icon: 'success',
            confirmButtonText: 'Genial',
            confirmButtonColor: '#0d6efd'
          }).then(() => {
            this.router.navigate(['/mis-reservas']);
          });
        } else {
          // Esto captura el error de "pista ocupada" que envía tu PHP
          Swal.fire({
            title: 'Pista no disponible',
            text: res.message || 'Elige otro horario, por favor.',
            icon: 'warning',
            confirmButtonText: 'Reintentar'
          });
        }
      },
      error: (err) => {
        // Si el error es 403, es que la sesión se cerró o no existe
        if (err.status === 403) {
          Swal.fire({
            title: 'Acceso denegado',
            text: 'Tu sesión ha expirado o no has iniciado sesión.',
            icon: 'error',
            confirmButtonText: 'Ir al Login',
            allowOutsideClick: false
          }).then(() => {
            this.router.navigate(['/login']);
          });
        } else {
          // Error genérico de servidor o CORS
          Swal.fire('Error', 'Hubo un problema con el servidor. Inténtalo más tarde.', 'error');
        }
      }
    });
  }
}

  cancelarReserva() {
    this.router.navigate(['/pistas']);
  }
}
