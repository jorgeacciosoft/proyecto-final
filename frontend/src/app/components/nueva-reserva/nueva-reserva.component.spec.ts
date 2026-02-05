import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NuevaReservaComponent } from './nueva-reserva.component';

describe('NuevaReservaComponent', () => {
  let component: NuevaReservaComponent;
  let fixture: ComponentFixture<NuevaReservaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NuevaReservaComponent]
    })
    .compileComponents();
    
    fixture = TestBed.createComponent(NuevaReservaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
