import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { map } from 'rxjs';

export const logueadoGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.checkSession().pipe(
    map(res => {
      if (res.isLoggedIn) {
        // Si ya está logueado, no le dejamos ver el login y lo mandamos a pistas
        router.navigate(['/pistas']);
        return false;
      } else {
        // Si no hay sesión, puede entrar al login
        return true;
      }
    })
  );
};
