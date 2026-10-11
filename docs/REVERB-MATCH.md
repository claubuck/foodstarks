# Qué tiene que coincidir para que Reverb acepte el broadcast

El error **"Pusher error: No matching application for ID [foodstarks]"** significa:  
el **servidor Reverb** recibe una petición con `app_id = foodstarks` y **no encuentra** ninguna aplicación registrada con ese ID.

## Quién envía y quién recibe

1. **Laravel (contenedor `app`)**  
   Cuando hacés "Enviar evento de prueba" o creás un pedido, Laravel usa:
   - `config('broadcasting.connections.reverb.app_id')` → debe ser **`"foodstarks"`**
   - `config('broadcasting.connections.reverb.key')` → **`"foodstarks-key"`**
   - `config('broadcasting.connections.reverb.options.host')` → **`"reverb"`** (en Docker)

   Esa config viene del `.env` y del `docker-compose` del servicio **app**.  
   Laravel manda una petición HTTP a `reverb:8080` con ese `app_id` y esa `key`.

2. **Servidor Reverb (contenedor `reverb`)**  
   Reverb recibe la petición, lee el **app_id** de la petición (ej. `"foodstarks"`) y busca en **su lista de aplicaciones** una cuya clave **`app_id`** sea exactamente esa.

   Esa lista sale de **`config('reverb.apps')`** (o `config('reverb.applications')`) cuando arranca el proceso **`php artisan reverb:start`**.

## Qué tiene que coincidir

| Origen              | Dónde se define                         | Valor que debe tener   |
|---------------------|-----------------------------------------|------------------------|
| **Laravel (envía)** | `broadcasting.connections.reverb.app_id` | `"foodstarks"`         |
| **Reverb (recibe)** | `reverb.apps[].app_id`                  | `"foodstarks"`         |

Es decir: el **mismo string** `"foodstarks"` en:

- La app Laravel (lo que envía en la petición).
- La config de Reverb (la lista de aplicaciones que el servidor Reverb carga al arrancar).

Si Laravel envía `app_id = "foodstarks"` y en la config de Reverb no hay ninguna app con `app_id === "foodstarks"`, Reverb responde "No matching application for ID [foodstarks]".

## Cómo comprobar cada lado

**Qué envía la app (Laravel):**

- En el navegador: `http://localhost/reverb-diagnostic`  
  Debe mostrar `"app_id": "foodstarks"`.

**Qué tiene Reverb al arrancar:**

- En el contenedor Reverb:  
  `docker compose exec reverb php artisan tinker --execute="print_r(config('reverb.apps'));"`  
  Debe mostrar al menos una app con `app_id => foodstarks`.

Si ambos coinciden y el error sigue, el proceso **`reverb:start`** puede estar leyendo la config de otra forma (por ejemplo otra clave o caché). En `config/reverb.php` están definidos tanto `apps` como `applications` con la misma lista para cubrir distintas versiones del servidor Reverb.
