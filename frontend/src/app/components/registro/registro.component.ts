import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth.service';
import { Router, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-registro',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './registro.component.html'
})
export class RegistroComponent {
  registroForm: FormGroup;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.registroForm = this.fb.group({
      nombre: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  confirmarRegistro() {
    if (this.registroForm.valid) {
      const credentials = { ...this.registroForm.value };

      this.authService.registrarUsuario(this.registroForm.value).subscribe({
        next: (res) => {
          if (res.status === 'success') {
            // Mostrar un mensaje rápido de bienvenida
            Swal.fire({
              title: '¡Cuenta creada!',
              html: '<p>Bienvenido a la plataforma</p><p class="text-muted small">Redirigiendo...</p>',
              icon: 'success',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
                // Hacer login automático después de 1 segundo
                setTimeout(() => {
                  this.loginAutomatico(credentials);
                }, 1000);
              }
            });
          } else {
            Swal.fire('Atención', res.message || 'Este correo ya está registrado', 'warning');
          }
        },
        error: (err) => {
          const mensajeError = err.error?.message || 'No se pudo conectar con el servidor';
          Swal.fire('Error', mensajeError, 'error');
        }
      });
    }
  }

  loginAutomatico(credentials: any) {
    this.authService.login(credentials).subscribe({
      next: (res) => {
        if (res.status === 'success') {
          Swal.close();
          Swal.fire({
            title: '¡Bienvenido!',
            text: `Sesión iniciada correctamente`,
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          }).then(() => {
            // Redireccionar a la app (listado de pistas)
            this.router.navigate(['/pistas']);
          });
        } else {
          Swal.fire('Error', 'No se pudo iniciar sesión automáticamente', 'error');
        }
      },
      error: (err) => {
        Swal.fire('Error', 'No se pudo iniciar sesión automáticamente', 'error');
        console.error('Error en login automático:', err);
      }
    });
  }
}
