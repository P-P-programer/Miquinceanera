<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                color: #0f172a;
                font-size: 12px;
            }

            h1 {
                font-size: 24px;
                margin: 0 0 8px;
            }

            p {
                margin: 0;
            }

            .meta {
                margin-bottom: 18px;
                color: #475569;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th, td {
                border: 1px solid #cbd5e1;
                padding: 10px;
                vertical-align: top;
            }

            th {
                background: #e2e8f0;
                text-align: left;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .note {
                color: #334155;
            }

            .muted {
                color: #64748b;
                font-size: 11px;
            }
        </style>
    </head>
    <body>
        <h1>Lista de canciones para el DJ</h1>
        <p class="meta">Generado: {{ $generatedAt }}</p>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invitado</th>
                    <th>Canción</th>
                    <th>Artista</th>
                    <th>Nota</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($songs as $index => $song)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $song->requester_name }}</td>
                        <td>{{ $song->song_title }}</td>
                        <td>{{ $song->artist_name ?: 'N/A' }}</td>
                        <td class="note">{{ $song->note ?: 'Sin nota' }}</td>
                        <td class="muted">{{ $song->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Aún no hay canciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>