import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from '../../services/data.service';
import Swal from 'sweetalert2';
import { Router } from '@angular/router';

@Component({
  selector: 'app-estadisticas',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './estadisticas.component.html',
  styleUrls: ['./estadisticas.component.css']
})
export class EstadisticasComponent implements OnInit {
  estadisticas: any = null;
  cargando: boolean = true;

  constructor(
    private dataService: DataService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.cargarEstadisticas();
  }

  cargarEstadisticas() {
    this.cargando = true;
    this.dataService.obtenerEstadisticas().subscribe({
      next: (data) => {
        console.log('Datos de estadísticas recibidos:', data);
        this.estadisticas = data;
        this.cargando = false;
      },
      error: (err) => {
        this.cargando = false;
        console.error('Error al cargar estadísticas:', err);
        if (err.status === 403) {
          Swal.fire('Acceso denegado', 'No tienes permisos para ver estadísticas', 'error').then(() => {
            this.router.navigate(['/']);
          });
        } else {
          const mensaje = err.error?.message || err.message || 'Error desconocido';
          Swal.fire('Error', `No se pudieron cargar las estadísticas: ${mensaje}`, 'error');
        }
      }
    });
  }

  volverAlPanel() {
    this.router.navigate(['/admin']);
  }
}
