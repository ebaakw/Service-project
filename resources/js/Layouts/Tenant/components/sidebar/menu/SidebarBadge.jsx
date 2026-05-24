import { usePage } from '@inertiajs/react';
import classNames from 'classnames';

const SidebarBadge = ({ count }) => {
    const { direction } = usePage().props;
    const isRtl = direction === 'rtl';

    if (!count || count <= 0) {
        return null;
    }

    return (
        <span
            className={classNames('right badge badge-warning', {
                'mr-2': isRtl,
                'ml-2': !isRtl,
            })}
            dir="ltr"
        >
            {count}
        </span>
    );
};

export default SidebarBadge;
