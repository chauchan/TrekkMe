import { Component } from '@angular/core';
import { IonHeader, IonToolbar, IonTitle, IonContent, IonBreadcrumb, IonBreadcrumbs } from '@ionic/angular';
import { RouterLink } from '@angular/router';
import { ExploreContainerComponent } from '../explore-container/explore-container.component';

@Component({
  selector: 'app-tab1',
  templateUrl: 'tab1.page.html',
  styleUrls: ['tab1.page.scss'],
  imports: [IonHeader, IonToolbar, IonTitle, IonContent, ExploreContainerComponent, IonBreadcrumb, IonBreadcrumbs, RouterLink],
})
export class Tab1Page {
  constructor() {}
}
