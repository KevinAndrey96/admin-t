<?php
/**
 * @var \WooPanel\View\View $this
 * @var string $csrf
 * @var string $username
 * @var string $error
 */
?>
<section class="login">
    <div class="login__card">
        <img class="login__logo" src="<?= $this->asset('/assets/icon.svg') ?>" alt="" width="48" height="48">
        <h1>Panel de la tienda</h1>
        <p class="muted">Ingresa con tu usuario de WordPress y una <strong>contraseña de aplicación</strong>. Tu contraseña normal no funciona aquí.</p>

        <?php if ($error !== ''): ?>
            <p class="alert" role="alert"><?= $this->e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/ingresar" class="stack" data-busy>
            <input type="hidden" name="_token" value="<?= $this->e($csrf) ?>">
            <label class="field">
                <span>Usuario o correo</span>
                <input name="usuario" value="<?= $this->e($username) ?>" autocomplete="username" required autofocus maxlength="100">
            </label>
            <label class="field">
                <span>Contraseña de aplicación</span>
                <input name="clave_aplicacion" type="password" autocomplete="current-password" required maxlength="200" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx">
            </label>
            <button class="button button--primary button--block" type="submit">Ingresar</button>
        </form>

        <details class="help">
            <summary>¿Cómo creo una contraseña de aplicación?</summary>
            <ol>
                <li>Entra al escritorio de WordPress con tu cuenta.</li>
                <li>Ve a <em>Usuarios → Perfil</em> y baja hasta <em>Contraseñas de aplicación</em>.</li>
                <li>Escribe un nombre, por ejemplo «Panel de la tienda», y pulsa <em>Añadir</em>.</li>
                <li>Copia la clave que aparece; WordPress solo la muestra una vez.</li>
            </ol>
            <p class="muted">Puedes revocarla cuando quieras desde el mismo lugar, y el panel dejará de tener acceso al instante.</p>
        </details>
    </div>
</section>
