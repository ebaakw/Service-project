import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import Button from '@/Components/Core/Button.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function CompanyRolesEdit({ role, permissions, permissionIds }) {
    const [selectedIds, setSelectedIds] = useState(Array.isArray(permissionIds) ? permissionIds : []);

    const { data, setData, put, processing, errors } = useForm({
        id: role.id,
        name: role.name || '',
        display_name: role.display_name || '',
        permissions: selectedIds,
    });

    const toggle = (id, checked) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (checked) next.add(id);
            else next.delete(id);
            return Array.from(next);
        });
    };

    const toggleGroup = (groupItems, checked) => {
        const ids = groupItems.map((p) => p.id);
        setSelectedIds((prev) => {
            const next = new Set(prev);
            ids.forEach((id) => {
                if (checked) next.add(id);
                else next.delete(id);
            });
            return Array.from(next);
        });
    };

    const submit = (e) => {
        e.preventDefault();
        setData('permissions', selectedIds);
        put(route('company.roles.update', role.id));
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
                                        disabled={role.is_system}
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
                                        disabled={role.is_system}
                                    />
                                    <div className="text-danger small mt-1">{errors.display_name}</div>
                                </div>
                            </div>

                            <div className="col-12">
                                {Object.entries(permissions || {}).map(([group, items]) => {
                                    const allChecked = items.every((p) => selectedIds.includes(p.id));
                                    return (
                                        <div key={group} className="card card-outline card-gray">
                                            <div className="card-header text-danger text-bold">
                                                <label className="mb-0">
                                                    <input
                                                        type="checkbox"
                                                        checked={allChecked}
                                                        onChange={(e) => toggleGroup(items, e.target.checked)}
                                                        disabled={role.is_system}
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
                                                                    checked={selectedIds.includes(p.id)}
                                                                    onChange={(e) => toggle(p.id, e.target.checked)}
                                                                    disabled={role.is_system}
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
                                    disabled={processing || role.is_system}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </CompanyDashboardLayout>
    );
}

