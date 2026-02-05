import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, tap } from 'rxjs';
import { environment } from '../../environments/environments';

@Injectable({
  providedIn: 'root'
})
export class AuthService {

  private AUTH_URL = environment.authUrl;

  // Este "sujeto" guardará el estado de la sesión (true/false)
  private loggedIn = new BehaviorSubject<boolean>(false);
  // Este guardará el rol (admin/user)
  private userRole = new BehaviorSubject<string>('');

  constructor(private http: HttpClient) {
    this.checkSession().subscribe(); // Comprobar sesión al cargar la app
  }

  // Getters para que los componentes se suscriban a los cambios
  isLoggedIn$ = this.loggedIn.asObservable();
  role$ = this.userRole.asObservable();

  login(credentials: any): Observable<any> {
    return this.http.post(`${this.AUTH_URL}?action=login`, credentials, { withCredentials: true }).pipe(
      tap((res: any) => {
        if (res.status === 'success') {
          this.loggedIn.next(true);
          this.userRole.next(res.user.rol);
        }
      })
    );
  }

  //Registrar usuario
  registrarUsuario(datos: any): Observable<any> {
    return this.http.post(`${this.AUTH_URL}?action=registrar_usuario`, datos);
  }
  checkSession(): Observable<any> {
    return this.http.get(`${this.AUTH_URL}?action=verificar_sesion`, { withCredentials: true }).pipe(
      tap((res: any) => {
        this.loggedIn.next(res.isLoggedIn);
        this.userRole.next(res.rol || '');
      })
    );
  }

  logout(): Observable<any> {
    return this.http.get(`${this.AUTH_URL}?action=cerrar_sesion`, { withCredentials: true }).pipe(
      tap(() => {
        this.loggedIn.next(false);
        this.userRole.next('');
      })
    );
  }
}
