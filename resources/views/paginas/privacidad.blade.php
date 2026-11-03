@extends(auth()->check() ? 'layouts.app' : 'layouts.acceso')
@section('titulo', 'Política de privacidad')
@section('contenido')
<div class="tarjeta">
    <h1>Política de privacidad</h1>
    <p>RecuperaYa trata los datos personales de la comunidad universitaria conforme a la Ley N.° 29733, Ley de Protección de Datos Personales, y su reglamento.</p>
    <ul>
        <li><strong>Datos que se recogen:</strong> nombre, correo institucional, código universitario (opcional), reportes de pérdida, reclamos y registro de entregas.</li>
        <li><strong>Finalidad:</strong> gestionar la devolución de objetos perdidos y encontrados en el campus.</li>
        <li><strong>Seguridad:</strong> las contraseñas y las respuestas de verificación se guardan cifradas; el catálogo no muestra datos personales.</li>
        <li><strong>Derechos:</strong> puedes solicitar el acceso, la rectificación o la eliminación de tus datos en la oficina de Bienestar Universitario.</li>
    </ul>
</div>
@endsection
