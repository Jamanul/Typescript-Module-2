"use strict";
{
    // type assertion
    let anything;
    anything = "next level web development";
    anything = true;
    // (anything as number) => this is type assersion . when i will directly tell the type 
    const convertedValue = (num) => {
        if (typeof num == 'string') {
            const result = parseFloat(num) * 1000;
            return `this is my result ${result}`;
        }
        if (typeof num == 'number') {
            const result = num * 1000;
            return result;
        }
    };
    const result1 = convertedValue("1000");
}
