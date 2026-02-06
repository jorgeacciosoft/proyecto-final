import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { DataService } from '../../services/data.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';
import Modal from 'bootstrap/js/dist/modal';
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
  pista: any = null;
  reservaTemporal: any = null;

  constructor(
    private route: ActivatedRoute,
    private fb: FormBuilder,
    private dataService: DataService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.pistaId = Number(this.route.snapshot.paramMap.get('id'));
    this.dataService.obtenerInstalacionPorId(this.pistaId).subscribe({
      next: (res) => {
        this.pista = res;
      },
      error: () => Swal.fire('Error', 'No se pudo cargar la información de la pista', 'error')
    });

    this.reservaForm = this.fb.group({
      instalacion_id: [this.pistaId, Validators.required],
      fecha: ['', Validators.required],
      hora: ['', [Validators.required, Validators.pattern("^([01]?[0-9]|2[0-3]):00$")]]
    });
  }

  // 2. MODIFICADO: Ahora no reserva, solo valida y abre la pasarela
  confirmarReserva() {
    if (this.reservaForm.valid) {
      // Guardamos los datos del formulario para usarlos tras el pago
      this.reservaTemporal = this.reservaForm.value;
      this.abrirPasarela();
    } else {
      Swal.fire('Atención', 'Por favor, completa la fecha y hora correctamente.', 'warning');
    }
  }

  cancelarReserva() {
    this.router.navigate(['/pistas']);
  }

  abrirPasarela() {
    const modalElement = document.getElementById('pagoModal');
    if (modalElement) {
      const modalPago = new Modal(modalElement);
      modalPago.show();
    }
  }

  procesarPago() {
    // 1. Simulamos una carga (Loading)
    Swal.fire({
      title: 'Procesando pago...',
      text: 'Conectando con la entidad bancaria',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    // 2. Simulamos retraso de red de 2 segundos
    setTimeout(() => {
      // 3. Llamamos al servicio con los datos guardados en reservaTemporal
      this.dataService.reservar(this.reservaTemporal).subscribe({
        next: (res) => {
          if (res.status === 'success') {
            Swal.fire({
              title: '¡Éxito!',
              text: 'Pago realizado y reserva confirmada',
              icon: 'success',
              confirmButtonColor: '#0d6efd'
            }).then(() => {
              // Cerramos el modal
              const modalElement = document.getElementById('pagoModal');
              const instance = Modal.getInstance(modalElement as Element) as any;
              if (instance) instance.hide();

              // Redirigimos al usuario
              this.router.navigate(['/mis-reservas']);
            });
          } else {
            Swal.fire('Error', res.message || 'La pista ya está ocupada', 'error');
          }
        },
        error: (err) => {
          if (err.status === 403) {
            Swal.fire('Sesión caducada', 'Inicia sesión de nuevo', 'error').then(() => {
              this.router.navigate(['/login']);
            });
          } else {
            Swal.fire('Error', 'Hubo un problema al procesar la reserva', 'error');
            console.log(err);
          }
        }
      });
    }, 2000);
  }
}
