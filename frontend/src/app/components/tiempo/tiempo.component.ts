import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

interface DatosTiempo {
  temperatura: number;
  sensacion: number;
  viento: number;
  lluvia: number;
  descripcion: string;
  icono: string;
  ciudad: string;
}

@Component({
  selector: 'app-tiempo',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tiempo.component.html',
  styleUrl: './tiempo.component.css'
})
export class TiempoComponent {
  abierto = false;
  ciudad = '';
  cargando = false;
  error = '';
  weather: DatosTiempo | null = null;

  constructor(private http: HttpClient) {}

  toggleWidget() {
    this.abierto = !this.abierto;
  }

  buscar() {
    const ciudad = this.ciudad.trim();
    if (!ciudad) return;

    this.cargando = true;
    this.error = '';
    this.weather = null;

    this.http.get<any[]>('https://nominatim.openstreetmap.org/search', {
      params: { q: ciudad, format: 'json', limit: '1' },
      headers: { 'Accept-Language': 'es' }
    }).subscribe({
      next: (resultados) => {
        if (!resultados || resultados.length === 0) {
          this.error = 'Ciudad no encontrada. Prueba con otro nombre.';
          this.cargando = false;
          return;
        }

        const { lat, lon, display_name } = resultados[0];
        const nombreCiudad = display_name.split(',')[0];

        this.http.get<any>('https://api.open-meteo.com/v1/forecast', {
          params: {
            latitude: lat,
            longitude: lon,
            current: 'temperature_2m,apparent_temperature,wind_speed_10m,precipitation,weather_code',
            wind_speed_unit: 'kmh',
            timezone: 'Europe/Madrid'
          }
        }).subscribe({
          next: (data) => {
            const c = data.current;
            this.weather = {
              temperatura: Math.round(c.temperature_2m),
              sensacion: Math.round(c.apparent_temperature),
              viento: Math.round(c.wind_speed_10m),
              lluvia: c.precipitation,
              ciudad: nombreCiudad,
              ...this.interpretarCodigo(c.weather_code)
            };
            this.cargando = false;
          },
          error: () => {
            this.error = 'Error al obtener el tiempo. Inténtalo de nuevo.';
            this.cargando = false;
          }
        });
      },
      error: () => {
        this.error = 'Error de conexión. Comprueba tu red.';
        this.cargando = false;
      }
    });
  }

  private interpretarCodigo(code: number): { descripcion: string; icono: string } {
    if (code === 0)  return { descripcion: 'Despejado',       icono: '☀️' };
    if (code <= 2)   return { descripcion: 'Poco nuboso',     icono: '⛅' };
    if (code === 3)  return { descripcion: 'Nublado',         icono: '☁️' };
    if (code <= 49)  return { descripcion: 'Niebla',          icono: '🌫️' };
    if (code <= 59)  return { descripcion: 'Llovizna',        icono: '🌦️' };
    if (code <= 69)  return { descripcion: 'Lluvia',          icono: '🌧️' };
    if (code <= 79)  return { descripcion: 'Nieve',           icono: '❄️' };
    if (code <= 84)  return { descripcion: 'Chubascos',       icono: '🌧️' };
    if (code <= 94)  return { descripcion: 'Tormenta',        icono: '⛈️' };
    return                  { descripcion: 'Tormenta fuerte', icono: '🌩️' };
  }
}
