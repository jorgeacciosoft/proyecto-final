import { Component, OnInit } from '@angular/core';
import { Router, RouterLink, RouterOutlet, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.css']
})
export class NavbarComponent implements OnInit {
  isLoggedIn: boolean = false;
  isMenuCollapsed: boolean = true;
  isAdmin: boolean = false;

  constructor(private authService: AuthService, private router: Router) {}

  ngOnInit() {
    // Nos suscribimos a los cambios de estado
    this.authService.isLoggedIn$.subscribe(status => this.isLoggedIn = status);
    this.authService.role$.subscribe(role => this.isAdmin = (role === 'admin'));
  }

  onLogout() {
    Swal.fire({
    title: '¿Estás seguro?',
    text: "Vas a cerrar tu sesión actual",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, salir',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      // Aquí llamas a tu servicio de logout
      this.authService.logout().subscribe(() => {
        this.router.navigate(['/login']);
      });
    }
  });
  }
}
