const removeAll = (parentContainer) => {
    if (!parentContainer) return;
    const childElements = parentContainer.querySelectorAll(':scope > li.menu-open');
    childElements.forEach((childElement) => {
        const childListElement = childElement.querySelector(':scope > ul');
        childElement.classList.remove('menu-is-opening', 'menu-open');
        if (childListElement) {
            childListElement.style.display = 'none';
        }
    });
}

const handleMouseEnter = (event) => {
    // Identify the list item and its submenu (if any)
    const root = event.currentTarget || event.target;
    const ele = root?.closest?.('li');
    if (!ele) return;

    const childListElement = ele.querySelector(':scope > ul'); // submenu if exists

    // If there is no submenu, do not interfere with normal link navigation
    if (!childListElement) {
        return;
    }

    event.preventDefault?.();
    event.stopPropagation();

    // Limit collapsing/expanding to the current sidebar only
    const parentContainer = ele.closest('ul.nav-sidebar') || ele.parentNode;

    if (ele.classList.contains('menu-open')) {
        removeAll(parentContainer);
    } else {
        removeAll(parentContainer);
        ele.classList.add('menu-is-opening', 'menu-open');
        childListElement.style.display = 'block';
    }
}

export default handleMouseEnter

