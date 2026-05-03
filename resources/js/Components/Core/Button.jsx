export const Button = ({ name, type, click, onClick, className, disabled = false }) => {
    const handler = onClick || click;
    return (
        <>
            <div className="form-group">
                <input
                    disabled={disabled}
                    onClick={handler}
                    value={name}
                    type={type}
                    className={`btn ${className}`}
                />
            </div>
        </>
    )
}

export default Button
