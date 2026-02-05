import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService } from '../../services/data.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-listado-pistas',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './listado-pistas.component.html',
  styleUrls: ['./listado-pistas.component.css']
})
export class ListadoPistasComponent implements OnInit {
  pistas: any[] = [];
  loading: boolean = true;

  constructor(private dataService: DataService, private router: Router) {}

  ngOnInit(): void {
    this.cargarPistas();
  }

  cargarPistas() {
    this.dataService.getPistas().subscribe({
      next: (data) => {
        this.pistas = data;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error al cargar pistas', err);
        this.loading = false;
      }
    });
  }

  irAReserva(pistaId: number) {
    // Navegamos al formulario de reserva pasando el ID de la pista
    this.router.navigate(['/nueva-reserva', pistaId]);
  }
}
