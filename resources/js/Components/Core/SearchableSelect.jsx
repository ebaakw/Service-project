import {useEffect, useMemo, useRef, useState} from "react";

const SearchableSelect = ({
    options = [],
    value,
    onChange,
    placeholder = '',
    allLabel = '',
}) => {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const rootRef = useRef(null);

    const selected = useMemo(() => {
        return options.find((o) => String(o.value) === String(value)) || null;
    }, [options, value]);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return options;
        return options.filter((o) => (o.label || '').toLowerCase().includes(q));
    }, [options, query]);

    useEffect(() => {
        const onDocClick = (e) => {
            if (!rootRef.current) return;
            if (!rootRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onDocClick);
        return () => document.removeEventListener('mousedown', onDocClick);
    }, []);

    return (
        <div ref={rootRef} className="position-relative">
            <button
                type="button"
                className="form-control d-flex justify-content-between align-items-center"
                onClick={() => setOpen((v) => !v)}
            >
                <span className="text-truncate">
                    {selected ? selected.label : (allLabel || placeholder || '')}
                </span>
                <span className="ms-2 text-muted">▾</span>
            </button>

            {open && (
                <div
                    className="position-absolute w-100 bg-white border rounded shadow-sm mt-1"
                    style={{zIndex: 1000}}
                >
                    <div className="p-2 border-bottom">
                        <input
                            className="form-control"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={placeholder}
                            autoFocus
                        />
                    </div>

                    <div style={{maxHeight: 260, overflowY: 'auto'}}>
                        {allLabel && (
                            <button
                                type="button"
                                className="btn btn-link text-start w-100 px-3 py-2"
                                onClick={() => {
                                    onChange('');
                                    setOpen(false);
                                    setQuery('');
                                }}
                            >
                                {allLabel}
                            </button>
                        )}

                        {filtered.length === 0 ? (
                            <div className="px-3 py-2 text-muted">-</div>
                        ) : (
                            filtered.map((o) => (
                                <button
                                    key={o.value}
                                    type="button"
                                    className="btn btn-link text-start w-100 px-3 py-2"
                                    onClick={() => {
                                        onChange(o.value);
                                        setOpen(false);
                                        setQuery('');
                                    }}
                                >
                                    {o.label}
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default SearchableSelect;

