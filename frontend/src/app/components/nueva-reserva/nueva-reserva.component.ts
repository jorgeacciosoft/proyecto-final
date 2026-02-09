import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidationErrors } from '@angular/forms';
import { DataService } from '../../services/data.service';
import { AuthService } from '../../services/auth.service';
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
  reservas: any[] = [];
  horasOcupadas: Set<string> = new Set();
  fechaMinima: string = '';

  constructor(
    private route: ActivatedRoute,
    private fb: FormBuilder,
    private dataService: DataService,
    private authService: AuthService,
    private router: Router
  ) {
    this.inicializarFechaMinima();
  }

  ngOnInit(): void {
    this.pistaId = Number(this.route.snapshot.paramMap.get('id'));

    // Cargar información de la pista
    this.dataService.obtenerInstalacionPorId(this.pistaId).subscribe({
      next: (res) => {
        this.pista = res;
      },
      error: () => Swal.fire('Error', 'No se pudo cargar la información de la pista', 'error')
    });

    // Cargar todas las reservas confirmadas
    this.dataService.obtenerTodasReservas().subscribe({
      next: (res) => {
        // Filtrar solo las reservas confirmadas
        this.reservas = res.filter((r: any) => r.estado === 'confirmada');
        console.log('Reservas cargadas:', this.reservas);
      },
      error: (err) => {
        console.error('Error cargando reservas:', err);
      }
    });

    // Crear validador personalizado para fecha mínima (hoy)
    this.reservaForm = this.fb.group({
      instalacion_id: [this.pistaId, Validators.required],
      fecha: ['', [Validators.required, this.fechaMinimaValidator.bind(this)]],
      hora: ['', [Validators.required, Validators.pattern("^([01]?[0-9]|2[0-3]):00$")]]
    });

    // Suscribirse a cambios en el campo fecha para actualizar horas disponibles
    this.reservaForm.get('fecha')?.valueChanges.subscribe((fecha) => {
      this.actualizarHorasOcupadas(fecha);
    });
  }

  // Validator personalizado para no permitir fechas pasadas
  fechaMinimaValidator(control: AbstractControl): ValidationErrors | null {
    if (!control.value) {
      return null; // No validar si está vacío
    }

    const fechaIngresada = new Date(control.value);
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    if (fechaIngresada < hoy) {
      return { fechaPasada: true };
    }

    return null;
  }

  // Inicializar la fecha mínima (hoy) en formato YYYY-MM-DD
  inicializarFechaMinima(): void {
    const hoy = new Date();
    const año = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const día = String(hoy.getDate()).padStart(2, '0');
    this.fechaMinima = `${año}-${mes}-${día}`;
  }

  // Actualizar horas ocupadas cuando cambia la fecha
  actualizarHorasOcupadas(fechaSeleccionada: string): void {
    this.horasOcupadas.clear();

    if (!fechaSeleccionada) {
      console.log('Fecha vacía, limpiando horas ocupadas');
      return;
    }

    // Filtrar las reservas de esta pista en la fecha seleccionada
    const reservasDelDia = this.reservas.filter(
      (r: any) =>
        r.instalacion_id == this.pistaId &&
        r.fecha === fechaSeleccionada &&
        r.estado === 'confirmada'
    );

    console.log(`Reservas para el ${fechaSeleccionada}:`, reservasDelDia);

    // Extraer las horas ocupadas (formato HH:00)
    reservasDelDia.forEach((r: any) => {
      // hora_inicio está en formato HH:MM o HH:MM:SS, extrae la hora
      const horaCompleta = r.hora_inicio.toString(); // Convertir a string por si acaso
      const [hora] = horaCompleta.split(':');
      const horaDeDisable = `${hora}:00`;
      this.horasOcupadas.add(horaDeDisable);
      console.log(`✗ Ocupada: ${horaDeDisable}`);
    });

    console.log('Horas ocupadas finales:', Array.from(this.horasOcupadas));
  }

  // Verificar si una hora está ocupada
  estaOcupada(hora: string): boolean {
    return this.horasOcupadas.has(hora);
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
            // Mostramos la referencia del pago si existe
            const referencia = res.pago?.referencia || 'N/A';
            Swal.fire({
              title: '¡Pago Exitoso!',
              html: `
                <p class="mb-2">Tu reserva ha sido confirmada</p>
                <div class="alert alert-success py-2">
                  <small><strong>Referencia:</strong> ${referencia}</small>
                </div>
              `,
              icon: 'success',
              confirmButtonColor: '#0d6efd',
              confirmButtonText: 'Ver mis reservas'
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
          console.error('Error completo:', err);
          if (err.status === 403) {
            Swal.fire('Sesión caducada', 'Inicia sesión de nuevo', 'error').then(() => {
              this.router.navigate(['/login']);
            });
          } else {
            // Mostramos más detalles del error para debugging
            const mensaje = err.error?.message || err.message || 'Error desconocido';
            Swal.fire('Error al procesar', mensaje, 'error');
          }
        }
      });
    }, 2000);
  }
}

