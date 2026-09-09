{{-- El nombre de la empresa, compuesto igual en todos los sitios donde sale.

     En minúscula a propósito, no por descuido. La "e" y la "c" van a la altura
     de las mayúsculas, como en el logotipo: 745/546 es la razón entre la altura
     de mayúsculas y la de la x que declara Plus Jakarta Sans.

     Y bajan a peso 500 porque agrandar la letra engorda su trazo: la base es
     peso 700, cuya asta mide 131 milésimas de em, y agrandada se iría a 179. El
     asta del peso 500 mide 93, que agrandada da 127: a un 3% de las 131 de al
     lado.

     Va aparte porque la barra superior de móvil lo escribía a su manera
     —"E&C Ingeniería", con mayúsculas— y quedaba distinto del de la barra
     lateral. Quien lo use pone su propio tamaño y peso en el elemento que lo
     envuelve; esto sólo compone las letras. --}}
@php $grande = 'text-[1.3645em] font-medium leading-[0]'; @endphp
<span class="{{ $grande }}">e</span>&amp;<span class="{{ $grande }}">c</span> ingeniería
