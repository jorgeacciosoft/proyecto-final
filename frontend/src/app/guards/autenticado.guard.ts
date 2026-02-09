import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { map } from 'rxjs';

export const autenticadoGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.checkSession().pipe(
    map(res => {
      // Verificamos si está logueado (cualquier rol, excepto sin loguearse)
      if (res.isLoggedIn) {
        return true;
      } else {
        // Si no está logueado, lo mandamos al registro
        router.navigate(['/registro']);
        return false;
      }
    })
  );
};
