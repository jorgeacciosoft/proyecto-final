import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ListadoPistasComponent } from './listado-pistas.component';

describe('ListadoPistasComponent', () => {
  let component: ListadoPistasComponent;
  let fixture: ComponentFixture<ListadoPistasComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ListadoPistasComponent]
    })
    .compileComponents();
    
    fixture = TestBed.createComponent(ListadoPistasComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
