import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environments';
import { environmentProd } from '../../environments/environments.prod';

@Injectable({
  providedIn: 'root'
})
export class DataService {
  private API_URL = environment.apiUrl;
  private UPLOADS_URL = environment.uploadsUrl;
  private AUTH_URL = environment.authUrl;

  constructor(private http: HttpClient) { }

  // Obtener catálogo de pistas (Petición asíncrona JSON)
  getPistas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API_URL}?action=obtener_pistas`);
  }

  // Crear una nueva reserva
  reservar(datos: any): Observable<any> {
    const body = {
      instalacion_id: datos.instalacion_id,
      fecha: datos.fecha,
      hora_inicio: datos.hora
    }
    return this.http.post(`${this.API_URL}?action=crear_reserva`, body, { withCredentials: true });
  }

  // Listar reservas del usuario logueado
  getMisReservas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API_URL}?action=mis_reservas`, { withCredentials: true });
  }

  //Cancelar una reserva
  cancelarReserva(reservaId: number): Observable<any> {
    // Enviamos el ID de la reserva al PHP
    return this.http.post(`${this.API_URL}?action=cancelar_reserva`, { reserva_id: reservaId }, { withCredentials: true });
  }

  // Listar todas las reservas (para admin)
  getTodasReservas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API_URL}?action=todas_reservas`, { withCredentials: true });
  }

  //Lista todos los usuarios (para admin)
  getUsuarios(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API_URL}?action=listar_todos_usuarios`, { withCredentials: true });
  }

  //Borra un usuario (para admin)
  borrarUsuario(userId: number): Observable<any> {
    return this.http.post(`${this.API_URL}?action=eliminar_usuario`, { user_id: userId }, { withCredentials: true });
  }

  //Actualizar un usuario (para admin)
  actualizarUsuario(datos: any): Observable<any> {
    return this.http.post(`${this.API_URL}?action=modificar_usuario`, {
      user_id: datos.id,
      nombre: datos.nombre,
      email: datos.email,
      password: datos.password || '',
      nuevo_rol: datos.rol
    }, { withCredentials: true });
  }

  //Crear una nueva pista (para admin)
  crearInstalacion(datos: any): Observable<any> {
    return this.http.post(`${this.API_URL}?action=crear_pista`, datos, { withCredentials: true });
  }

  //Eliminar una pista (para admin)
  eliminarInstalacion(instalacionId: number): Observable<any> {
    return this.http.post(`${this.API_URL}?action=eliminar_pista`, { instalacion_id: instalacionId }, { withCredentials: true });
  }

  //Modificar una pista (para admin)
  modificarInstalacion(formData: FormData): Observable<any> {
    // Enviamos el FormData completo con la acción correspondiente
    return this.http.post(`${this.API_URL}?action=modificar_pista`, formData, { withCredentials: true });
  }

  obtenerInstalacionPorId(id: number): Observable<any> {
    return this.http.get(`${this.API_URL}?action=obtener_instalacion&id=${id}`, { withCredentials: true });
  }

  // Obtener estadísticas de ingresos (para admin)
  obtenerEstadisticas(): Observable<any> {
    return this.http.get(`${this.API_URL}?action=estadisticas`, { withCredentials: true });
  }

  // Obtener todas las reservas confirmadas
  obtenerTodasReservas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API_URL}?action=obtener_reservas_confirmadas`);
  }
}
