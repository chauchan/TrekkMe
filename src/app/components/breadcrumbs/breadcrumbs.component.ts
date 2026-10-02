import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { IonBreadcrumbs, IonBreadcrumb } from '@ionic/angular';
import { HistorialService } from '../../services/historial';

@Component({
  selector: 'app-breadcrumbs',
  templateUrl: './breadcrumbs.component.html',
  styleUrls: ['./breadcrumbs.component.scss'],
  imports: [IonBreadcrumbs, IonBreadcrumb, RouterLink],
})
export class BreadcrumbsComponent {
  historial = inject(HistorialService);
}

