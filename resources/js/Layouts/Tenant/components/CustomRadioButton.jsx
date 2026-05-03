import React from "react";
import classNames from "classnames";
import {limitTextLength} from "@/Utility/Hooks/useText.jsx";
import SmartImage from "@/Components/Core/SmartImage.jsx";

const CustomRadioButton = ({
    id,
    name,
    labelText,
    value,
    checked,
    onClick,
    src,
    thumbSrc,
    scrollRootRef,
    regularPrice,
    urgentPrice,
    typePrice,
}) => {

    return (
        <>
            <div title={labelText} style={{cursor: 'pointer', width: '100px', height: '120px'}} onClick={onClick}
                 className={classNames('rounded shadow-sm mb-3',{'border border-secondary border-5': checked})}>

                <div className="d-flex justify-content-center pt-1">
                    <SmartImage
                        src={src}
                        thumbSrc={thumbSrc}
                        alt={name ?? ''}
                        width={70}
                        height={70}
                        scrollRootRef={scrollRootRef}
                        imgClassName="mx-auto d-block"
                    />
                </div>

                <p className={classNames('fw-bold my-n1 text-wrap', {
                    'badge bg-gray': checked,
                    'badge bg-white text-dark': !checked
                })}>
                    {limitTextLength(labelText)}
                </p>
                <p className={classNames('my-n1', {'font-weight-bold': checked})}>
                    {typePrice ? urgentPrice : regularPrice}
                </p>

                <input
                    type="hidden"
                    id={id}
                    name={name}
                    value={value}
                    checked={checked}
                    onChange={() => {}}
                />
            </div>
        </>
    )
}

export default React.memo(CustomRadioButton)
