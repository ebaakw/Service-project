import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import Button from '@/Components/Core/Button.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

const statusOptions = [
    { value: 'active', labelKey: 'tenant.active' },
    { value: 'inactive', labelKey: 'tenant.no_active' },
    { value: 'suspended', labelKey: 'tenant.suspended' },
];

export default function CompanyUsersCreate({ roles }) {
    const RequiredMark = () => (
        <span className="text-danger" title="Required">
            {' '}
            *
        </span>
    );

    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
    const [attemptedSubmit, setAttemptedSubmit] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name_ar: '',
        name_en: '',
        email: '',
        phone: '',
        status: 'active',
        password: '',
        password_confirmation: '',
        role_id: roles?.[0]?.id ?? '',
    });

    const focusField = (name) => {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return;
        if (typeof el.scrollIntoView === 'function') {
            el.scrollIntoView({ block: 'center' });
        }
        if (typeof el.focus === 'function') {
            el.focus();
        }
    };

    const focusFirstError = (errs) => {
        const first = Object.keys(errs || {})[0];
        if (first) focusField(first);
    };

    const isEmpty = (v) => String(v ?? '').trim() === '';

    const validateRequired = () => {
        const required = ['name_ar', 'email', 'status', 'password', 'password_confirmation', 'role_id'];
        const firstMissing = required.find((k) => isEmpty(data[k]));
        if (firstMissing) {
            focusField(firstMissing);
            return false;
        }
        return true;
    };

    const submit = (e) => {
        e.preventDefault();
        setAttemptedSubmit(true);
        if (!validateRequired()) {
            return;
        }

        post(route('company.users.store'), {
            onError: (errs) => focusFirstError(errs),
        });
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.users_and_roles">
            <form onSubmit={submit}>
                <div className="card card-outline card-primary">
                    <div className="card-header" />
                    <div className="card-body">
                        <div className="row">
                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>
                                        {__('dashboard.forms.name_ar')}
                                        <RequiredMark />
                                    </label>
                                    <input
                                        className={`form-control ${errors.name_ar || (attemptedSubmit && isEmpty(data.name_ar)) ? 'is-invalid' : ''}`}
                                        name="name_ar"
                                        value={data.name_ar}
                                        onChange={(e) => setData('name_ar', e.target.value)}
                                    />
                                    <div className="text-danger small mt-1">{errors.name_ar}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('dashboard.forms.name_en')}</label>
                                    <input className="form-control" name="name_en" value={data.name_en} onChange={(e) => setData('name_en', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.name_en}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.email')}
                                        <RequiredMark />
                                    </label>
                                    <input
                                        className={`form-control ${errors.email || (attemptedSubmit && isEmpty(data.email)) ? 'is-invalid' : ''}`}
                                        name="email"
                                        type="email"
                                        dir="ltr"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    <div className="text-danger small mt-1">{errors.email}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.phone')}</label>
                                    <input className="form-control" name="phone" dir="ltr" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.phone}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.password')}
                                        <RequiredMark />
                                    </label>
                                    <div className="input-group">
                                        <input
                                            className={`form-control ${errors.password || (attemptedSubmit && isEmpty(data.password)) ? 'is-invalid' : ''}`}
                                            name="password"
                                            type={showPassword ? 'text' : 'password'}
                                            dir="ltr"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                        />
                                        <div className="input-group-append">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary"
                                                onClick={() => setShowPassword((v) => !v)}
                                                aria-label="Toggle password visibility"
                                            >
                                                <i className={showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'} />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="text-danger small mt-1">{errors.password}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.password_confirmation')}
                                        <RequiredMark />
                                    </label>
                                    <div className="input-group">
                                        <input
                                            className={`form-control ${errors.password_confirmation || (attemptedSubmit && isEmpty(data.password_confirmation)) ? 'is-invalid' : ''}`}
                                            name="password_confirmation"
                                            type={showPasswordConfirmation ? 'text' : 'password'}
                                            dir="ltr"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                        />
                                        <div className="input-group-append">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary"
                                                onClick={() => setShowPasswordConfirmation((v) => !v)}
                                                aria-label="Toggle password confirmation visibility"
                                            >
                                                <i className={showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'} />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.status')}
                                        <RequiredMark />
                                    </label>
                                    <select
                                        className={`custom-select ${errors.status || (attemptedSubmit && isEmpty(data.status)) ? 'is-invalid' : ''}`}
                                        name="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        {statusOptions.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {__(o.labelKey)}
                                            </option>
                                        ))}
                                    </select>
                                    <div className="text-danger small mt-1">{errors.status}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.select_permissions')}
                                        <RequiredMark />
                                    </label>
                                    <select
                                        className={`custom-select ${errors.role_id || (attemptedSubmit && isEmpty(data.role_id)) ? 'is-invalid' : ''}`}
                                        name="role_id"
                                        value={data.role_id}
                                        onChange={(e) => setData('role_id', e.target.value)}
                                    >
                                        {roles.map((r) => (
                                            <option key={r.id} value={r.id}>
                                                {r.display_name}
                                            </option>
                                        ))}
                                    </select>
                                    <div className="text-danger small mt-1">{errors.role_id}</div>
                                </div>
                            </div>

                            <div className="col-sm-12 mt-3">
                                <Button name={__('tenant.submit')} type="submit" className="btn-primary px-5 py-2" disabled={processing} />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </CompanyDashboardLayout>
    );
}

