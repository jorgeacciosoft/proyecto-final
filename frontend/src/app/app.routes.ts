import { Routes } from '@angular/router';
import { adminGuard } from './guards/admin.guard';
import { logueadoGuard } from './guards/logueado.guard';
import { autenticadoGuard } from './guards/autenticado.guard';
import { LoginComponent } from './components/login/login.component';
import { PanelAdministradorComponent } from './components/panel-administrador/panel-administrador.component';
import { ListadoPistasComponent } from './components/listado-pistas/listado-pistas.component';
import { MisReservasComponent } from './components/mis-reservas/mis-reservas.component';
import { NuevaReservaComponent } from './components/nueva-reserva/nueva-reserva.component';
import { RegistroComponent } from './components/registro/registro.component';
import { EstadisticasComponent } from './components/estadisticas/estadisticas.component';
export const routes: Routes = [
  // Ruta inicial: Redirige al login o al listado de pistas
  { path: '', redirectTo: '/pistas', pathMatch: 'full' },
  { path: 'login', component: LoginComponent, canActivate:[logueadoGuard] },

  //Rutas de administrador
  {
    path: 'admin',
    component: PanelAdministradorComponent,
    canActivate: [adminGuard] // Aquí aplicamos la protección
  },
  {
    path: 'estadisticas',
    component: EstadisticasComponent,
    canActivate: [adminGuard]
  },

  //Rutas de usuario
  { path: 'pistas', component: ListadoPistasComponent },
  { path: 'mis-reservas', component: MisReservasComponent },
  { path: 'nueva-reserva/:id', component: NuevaReservaComponent, canActivate: [autenticadoGuard] },

  //Ruta de registro
  {
    path: 'registro',component: RegistroComponent
  },

  // Ruta comodín: Si la URL no existe, vuelve a pistas
  { path: '**', redirectTo: '/pistas' }

];
