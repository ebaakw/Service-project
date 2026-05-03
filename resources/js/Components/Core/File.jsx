const Input = ({name, type, value, placeholder}) => {
    return (
        <>
            <div className="form-group">
                <label>{name} <span className="text-danger">*</span></label>
                <input
                    name={name}
                    type={type}
                    value={value}
                    className=""
                    placeholder={placeholder}
                    required
                />
            </div>
        </>
    )
}

export default Input
