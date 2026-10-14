# Componente PasswordInput

## Descripción

Componente Vue 3 reutilizable para campos de contraseña con funcionalidad de mostrar/ocultar contraseña.

## Características

-   ✅ Toggle para mostrar/ocultar contraseña
-   ✅ Iconos SVG responsivos (ojo abierto/cerrado)
-   ✅ v-model compatible
-   ✅ Estilos Tailwind CSS personalizables
-   ✅ Tooltip descriptivo
-   ✅ Mantiene el foco después del toggle
-   ✅ Transiciones suaves

## Uso

```vue
<template>
    <div>
        <label>Contraseña</label>
        <PasswordInput
            v-model="form.password"
            placeholder="Ingrese su contraseña"
            input-class="custom-input-class"
        />
    </div>
</template>

<script setup>
import PasswordInput from "@/Components/PasswordInput.vue";
import { ref } from "vue";

const form = ref({
    password: "",
});
</script>
```

## Props

| Prop          | Tipo   | Default                                                                                                        | Descripción                      |
| ------------- | ------ | -------------------------------------------------------------------------------------------------------------- | -------------------------------- |
| `modelValue`  | String | `''`                                                                                                           | Valor de la contraseña (v-model) |
| `placeholder` | String | `'Ingrese la contraseña'`                                                                                      | Placeholder del input            |
| `inputClass`  | String | `'form-input w-full rounded-md shadow-sm pr-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'` | Clases CSS para el input         |

## Eventos

| Evento              | Descripción                               |
| ------------------- | ----------------------------------------- |
| `update:modelValue` | Se emite cuando el valor cambia (v-model) |

## Ejemplos de implementación

### Formulario de Login

```vue
<PasswordInput
    v-model="loginForm.password"
    placeholder="Contraseña"
    input-class="login-input"
/>
```

### Formulario de Registro

```vue
<PasswordInput v-model="registerForm.password" placeholder="Nueva contraseña" />

<PasswordInput
    v-model="registerForm.password_confirmation"
    placeholder="Confirmar contraseña"
/>
```

### Cambio de contraseña

```vue
<PasswordInput
    v-model="changePasswordForm.current_password"
    placeholder="Contraseña actual"
/>

<PasswordInput
    v-model="changePasswordForm.new_password"
    placeholder="Nueva contraseña"
/>
```

## Ubicación

`resources/js/Components/PasswordInput.vue`

## Implementado en

-   ✅ `resources/js/Pages/Usuario/CreateUsuario.vue`
-   📝 Disponible para otros formularios

## Mejoras futuras

-   [ ] Validación de fortaleza de contraseña
-   [ ] Indicador visual de fortaleza
-   [ ] Soporte para atributos HTML adicionales
-   [ ] Modo de solo lectura
