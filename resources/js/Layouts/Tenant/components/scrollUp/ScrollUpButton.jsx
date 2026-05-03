import React, { useEffect, useState } from "react";
import { usePage } from "@inertiajs/react";
import './scrollUpButton.css'

const ScrollUpButton = () => {
    const { locale } = usePage().props;
    const [isVisible, setIsVisible] = useState(false);

    // Show or hide the button based on scroll position
    const toggleVisibility = () => {
        setIsVisible(window.scrollY > 200);
    };

    // Scroll to top when button is clicked
    const scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    useEffect(() => {
        window.addEventListener("scroll", toggleVisibility);
        return () => window.removeEventListener("scroll", toggleVisibility);
    }, []);

    // Position styles based on locale
    const buttonPosition = locale === "ar" ? { left: "20px" } : { right: "20px" };

    return (
        isVisible && (
            <button
                className="btn btn-primary position-fixed scroll-up-btn"
                style={{ bottom: "30px", zIndex: 99, ...buttonPosition }}
                onClick={scrollToTop}
            >
                <i className="fas fa-arrow-up"></i>
            </button>
        )
    );
};

export default ScrollUpButton;
