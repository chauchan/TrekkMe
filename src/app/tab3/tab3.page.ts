import { Component } from '@angular/core';
import { IonHeader, IonToolbar, IonTitle, IonContent, IonBreadcrumbs, IonBreadcrumb } from '@ionic/angular';
import { RouterLink } from '@angular/router';
import { ExploreContainerComponent } from '../explore-container/explore-container.component';

@Component({
  selector: 'app-tab3',
  templateUrl: 'tab3.page.html',
  styleUrls: ['tab3.page.scss'],
  imports: [IonHeader, IonToolbar, IonTitle, IonContent, ExploreContainerComponent, IonBreadcrumbs, IonBreadcrumb, RouterLink],
})
export class Tab3Page {
  constructor() {}
}
