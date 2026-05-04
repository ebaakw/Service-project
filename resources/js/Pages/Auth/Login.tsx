import ApplicationLogo from '@/Components/ApplicationLogo';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type LoginStrings = {
    title: string;
    hint: string;
    hero_title: string;
    hero_subtitle: string;
    brand_subtitle: string;
    switch_language: string;
    show_password: string;
    hide_password: string;
    sign_in: string;
    remember_me: string;
    need_language_help: string;
    email_placeholder: string;
    password_placeholder: string;
    forgot_password: string;
};

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const page = usePage<
        PageProps & {
            loginStrings?: LoginStrings;
            appName?: string;
        }
    >().props;

    const loginStrings = page.loginStrings;
    const appName = page.appName ?? 'App';
    const locale = page.locale ?? 'en';

    const oppositeLocale = locale === 'ar' ? 'en' : 'ar';

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const [showPassword, setShowPassword] = useState(false);

    const s = loginStrings;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const langSwitchHref = route('locale.switch', { locale: oppositeLocale });

    if (!s) {
        return null;
    }

    return (
        <>
            <Head title={s.title} />

            <div className="els-auth els-auth--page els-auth--split hold-transition">
                <div className="els-auth__split">
                    <div className="els-auth__panel-visual">
                        <div className="els-auth__hero">
                            <h2 className="els-auth__hero-title">{s.hero_title}</h2>
                            <p className="els-auth__hero-subtitle">{s.hero_subtitle}</p>
                        </div>
                    </div>

                    <div className="els-auth__panel-form">
                        <div className="els-auth__wrap">
                            <div className="els-auth__topbar">
                                <a className="els-auth__brand" href="/">
                                    <ApplicationLogo className="h-10 w-10 shrink-0 fill-current text-sky-600" />
                                    <div>
                                        <div className="els-auth__brand-title">{appName}</div>
                                        <div className="els-auth__brand-subtitle">{s.brand_subtitle}</div>
                                    </div>
                                </a>

                                <a className="els-auth__lang" href={langSwitchHref}>
                                    <i className="fa fa-globe" aria-hidden="true" />
                                    <span>{s.switch_language}</span>
                                </a>
                            </div>

                            <div className="els-auth__card card card-outline card-primary">
                                <div className="els-auth__card-inner card-body">
                                    <h1 className="els-auth__headline">{s.title}</h1>
                                    <p className="els-auth__hint">{s.hint}</p>

                                    {(status ||
                                        errors?.email ||
                                        errors?.password ||
                                        (errors as { error?: string }).error) && (
                                        <div className="els-auth__errors alert alert-danger small mb-3">
                                            {status ? <div>{status}</div> : null}
                                            {errors?.email ? (
                                                <div>{Array.isArray(errors.email) ? errors.email[0] : errors.email}</div>
                                            ) : null}
                                            {errors?.password ? (
                                                <div>
                                                    {Array.isArray(errors.password)
                                                        ? errors.password[0]
                                                        : errors.password}
                                                </div>
                                            ) : null}
                                            {(errors as { error?: string }).error ? (
                                                <div>
                                                    {Array.isArray((errors as { error?: string }).error)
                                                        ? (errors as { error: string[] }).error[0]
                                                        : (errors as { error?: string }).error}
                                                </div>
                                            ) : null}
                                        </div>
                                    )}

                                    <form onSubmit={submit} method="post" noValidate>
                                        <div className="els-auth__input mb-2">
                                            <div className="input-group">
                                                <input
                                                    value={data.email}
                                                    type="email"
                                                    name="email"
                                                    className="form-control"
                                                    placeholder={s.email_placeholder}
                                                    autoComplete="username"
                                                    dir="ltr"
                                                    onChange={(e) => setData('email', e.target.value)}
                                                />
                                                <div className="input-group-append">
                                                    <span className="input-group-text">
                                                        <span className="fas fa-envelope" aria-hidden="true" />
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        {errors.email ? (
                                            <div className="text-danger small mb-2">{errors.email}</div>
                                        ) : null}

                                        <div className="els-auth__input mb-2">
                                            <div className="input-group">
                                                <input
                                                    type={showPassword ? 'text' : 'password'}
                                                    name="password"
                                                    value={data.password}
                                                    className="form-control"
                                                    placeholder={s.password_placeholder}
                                                    autoComplete="current-password"
                                                    dir="ltr"
                                                    onChange={(e) => setData('password', e.target.value)}
                                                />
                                                <div className="input-group-append">
                                                    <button
                                                        type="button"
                                                        className="btn els-auth__addon-btn"
                                                        onClick={() => setShowPassword((v) => !v)}
                                                        aria-label={showPassword ? s.hide_password : s.show_password}
                                                    >
                                                        <i
                                                            className={showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'}
                                                            aria-hidden="true"
                                                        />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        {errors.password ? (
                                            <div className="text-danger small mb-2">{errors.password}</div>
                                        ) : null}

                                        <div className="row align-items-center mt-3">
                                            <div className="col-8">
                                                <label
                                                    className="els-auth__remember d-flex align-items-center gap-2"
                                                    htmlFor="remember"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        id="remember"
                                                        name="remember"
                                                        checked={data.remember}
                                                        onChange={(e) =>
                                                            setData('remember', e.target.checked)
                                                        }
                                                    />
                                                    <span>{s.remember_me}</span>
                                                </label>
                                            </div>

                                            <div className="col-4">
                                                <button
                                                    type="submit"
                                                    className="btn btn-primary btn-block els-auth__btn"
                                                    disabled={processing}
                                                >
                                                    {s.sign_in}
                                                </button>
                                            </div>
                                        </div>

                                        {canResetPassword ? (
                                            <div className="mt-3 text-center">
                                                <Link
                                                    href={route('password.request')}
                                                    className="els-auth__forgot small"
                                                >
                                                    {s.forgot_password}
                                                </Link>
                                            </div>
                                        ) : null}
                                    </form>
                                </div>

                                <div className="els-auth__footer">
                                    <span>{s.need_language_help}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
