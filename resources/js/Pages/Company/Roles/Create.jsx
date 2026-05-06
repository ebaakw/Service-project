import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import Button from '@/Components/Core/Button.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { useForm } from '@inertiajs/react';

export default function CompanyRolesCreate({ permissions }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        display_name: '',
        permissions: [],
    });

    const toggle = (id, checked) => {
        setData((prev) => {
            const next = new Set(prev.permissions ?? []);
            if (checked) next.add(id);
            else next.delete(id);
            return { ...prev, permissions: Array.from(next) };
        });
    };

    const toggleGroup = (groupItems, checked) => {
        const ids = groupItems.map((p) => p.id);
        setData((prev) => {
            const next = new Set(prev.permissions ?? []);
            ids.forEach((id) => {
                if (checked) next.add(id);
                else next.delete(id);
            });
            return { ...prev, permissions: Array.from(next) };
        });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('company.roles.store'));
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
                                    <label>{__('tenant.name')}</label>
                                    <input
                                        className="form-control"
                                        name="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder={__('role.role_key')}
                                        dir="ltr"
                                    />
                                    <div className="text-danger small mt-1">{errors.name}</div>
                                </div>
                            </div>

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label>{__('tenant.roles')}</label>
                                    <input
                                        className="form-control"
                                        name="display_name"
                                        value={data.display_name}
                                        onChange={(e) => setData('display_name', e.target.value)}
                                        placeholder={__('tenant.name')}
                                    />
                                    <div className="text-danger small mt-1">{errors.display_name}</div>
                                </div>
                            </div>

                            <div className="col-12">
                                {Object.entries(permissions || {}).map(([group, items]) => {
                                    const allChecked = items.every((p) =>
                                        (data.permissions ?? []).includes(p.id),
                                    );
                                    return (
                                        <div key={group} className="card card-outline card-gray">
                                            <div className="card-header text-danger text-bold">
                                                <label className="mb-0">
                                                    <input
                                                        type="checkbox"
                                                        checked={allChecked}
                                                        onChange={(e) => toggleGroup(items, e.target.checked)}
                                                    />
                                                    <span className="mx-1">{__('role.' + group)}</span>
                                                </label>
                                            </div>
                                            <div className="card-body">
                                                <div className="row">
                                                    {items.map((p) => (
                                                        <div className="col-md-3" key={p.id}>
                                                            <label className="mx-1">
                                                                <input
                                                                    className="mx-1"
                                                                    type="checkbox"
                                                                    checked={(data.permissions ?? []).includes(
                                                                        p.id,
                                                                    )}
                                                                    onChange={(e) => toggle(p.id, e.target.checked)}
                                                                />
                                                                {__('role.' + p.key)}
                                                            </label>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="col-sm-12 mt-3">
                                <Button
                                    name={__('tenant.submit')}
                                    type="submit"
                                    className="btn-primary px-5 py-2"
                                    disabled={processing}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </CompanyDashboardLayout>
    );
}

