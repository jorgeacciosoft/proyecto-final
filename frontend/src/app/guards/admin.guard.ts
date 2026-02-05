import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { map } from 'rxjs';

export const adminGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.checkSession().pipe(
    map(res => {
      // Verificamos si está logueado y si su rol es admin
      if (res.isLoggedIn && res.rol === 'admin') {
        return true;
      } else {
        // Si no es admin, lo mandamos al login o inicio
        router.navigate(['/login']);
        return false;
      }
    })
  );
};
