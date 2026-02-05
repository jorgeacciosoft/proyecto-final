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
    this.authService.registrarUsuario(this.registroForm.value).subscribe({
      next: (res) => {
        // Creación exitosa, mostramos mensaje y redirigimos a login
        if (res.status === 'success') {
          Swal.fire('¡Cuenta creada!', 'Ya puedes iniciar sesión con tus credenciales.', 'success')
            .then(() => this.router.navigate(['/login']));
        } else {
          // Si el backend detecta el email duplicado y manda un mensaje de error
          Swal.fire('Atención', res.message || 'Este correo ya está registrado', 'warning');
        }
      },
      error: (err) => {
        // Manejo de errores de red o errores 4xx/5xx del servidor
        const mensajeError = err.error?.message || 'No se pudo conectar con el servidor';
        Swal.fire('Error', mensajeError, 'error');
      }
    });
  }
}
}
