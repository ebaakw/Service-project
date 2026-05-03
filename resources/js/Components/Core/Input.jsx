const Input = ({name, type, value, placeholder, label, props}) => {
    let labelHtml;
    if (label) {
        labelHtml = <label>{label} <span className="text-danger">*</span></label>;
    }

    return (
        <>
            <div className="form-group">
                {labelHtml}
                <input
                    {...props}
                    name={name}
                    type={type}
                    value={value}
                    className="form-control"
                    placeholder={placeholder}
                />
            </div>
        </>
    )
}

export default Input
