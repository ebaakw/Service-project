import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import Button from '@/Components/Core/Button.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { useForm } from '@inertiajs/react';

const statusOptions = [
    { value: 'active', labelKey: 'tenant.active' },
    { value: 'inactive', labelKey: 'tenant.no_active' },
    { value: 'suspended', labelKey: 'tenant.suspended' },
];

export default function PlatformUsersEdit({ user, roles }) {
    const { data, setData, put, processing, errors } = useForm({
        id: user.id,
        name_ar: user.name_ar ?? '',
        name_en: user.name_en ?? '',
        email: user.email ?? '',
        phone: user.phone ?? '',
        status: user.status ?? 'active',
        password: '',
        password_confirmation: '',
        role_id: user.role_id ?? roles?.[0]?.id ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('platform.users.update', user.id));
    };

    return (
        <PlatformAdminLayout breadcrumbKey="dashboard.nav.users_and_roles">
            <form onSubmit={submit}>
                <div className="card card-outline card-primary">
                    <div className="card-header" />
                    <div className="card-body">
                        <div className="row">
                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('dashboard.forms.name_ar')}</label>
                                    <input className="form-control" value={data.name_ar} onChange={(e) => setData('name_ar', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.name_ar}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('dashboard.forms.name_en')}</label>
                                    <input className="form-control" value={data.name_en} onChange={(e) => setData('name_en', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.name_en}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.email')}</label>
                                    <input className="form-control" type="email" dir="ltr" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.email}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.phone')}</label>
                                    <input className="form-control" dir="ltr" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.phone}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.password')}</label>
                                    <input className="form-control" type="password" dir="ltr" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                                    <div className="text-danger small mt-1">{errors.password}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.password_confirmation')}</label>
                                    <input className="form-control" type="password" dir="ltr" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.status')}</label>
                                    <select className="custom-select" value={data.status} onChange={(e) => setData('status', e.target.value)}>
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
                                    <label>{__('tenant.select_permissions')}</label>
                                    <select className="custom-select" value={data.role_id} onChange={(e) => setData('role_id', e.target.value)}>
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
        </PlatformAdminLayout>
    );
}

