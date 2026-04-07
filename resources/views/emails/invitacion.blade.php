<x-mail::message>
# Te han invitado a Selcosi Flota

Has sido invitado a unirte al sistema de gestión de flota de **Selcosi Export S.A.C.**

Haz clic en el botón para completar tu registro. El enlace es válido hasta el **{{ $expira }}**.

<x-mail::button :url="$url">
Completar registro
</x-mail::button>

Si no esperabas esta invitación, puedes ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
