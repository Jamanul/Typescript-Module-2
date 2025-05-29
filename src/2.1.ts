{
    // type assertion
    let anything :any;
    anything = "next level web development"
    anything = true;
    // (anything as number) => this is type assersion . when i will directly tell the type 
    const convertedValue =(num:string| number):string| number| undefined=>{
        if(typeof num =='string'){
            const result = parseFloat(num)*1000
            return `this is my result ${result}`;
        }
        if(typeof num =='number'){
            const result = num*1000
            return result;
        }
    }

    const result1 = convertedValue("1000") as string;
    const result2 = convertedValue(1000) as number;

    type CustomError ={
        message:string
    }

    try {
        
    } catch (error) {
        console.log((error as CustomError).message)
    }


}