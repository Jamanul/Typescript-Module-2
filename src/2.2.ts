{

    // function type and interface
    type Add = (num1:number,num2:number)=>number;
    
    interface Add2 {
        (num1:number,num2:number): number
    }
    
    
    const add:Add2 = (num1,num2)=>num1+num2

// array type and interface

// type Roll = number[]

interface Roll2 {
    [index:number]:number
}

let classRoll:Roll2 = [1,2,3,4]

// classRoll.push(55)

}



// interface Roll2 {
//     [index: number]: number;
// }
// // This defines a number-indexed object, where the index is a number and the value is also a number. Technically, it allows things like:


// let obj: Roll2 = {
//   0: 1,
//   1: 2,
//   2: 3
// };
// // This is not guaranteed to have array methods like .push(), .pop(), etc., because it's not strictly an array, even though it resembles one.


// let classRoll: Roll2 = [1, 2, 3, 4];
// classRoll.push(55); // Error
// // TypeScript treats classRoll as Roll2, which doesn't have .push() defined. So you'll get:


// // Property 'push' does not exist on type 'Roll2'.
// // ✅ Fix:
// // If you want to use array methods like .push(), you should define Roll2 as an actual array:


// type Roll2 = number[];

// let classRoll: Roll2 = [1, 2, 3, 4];
// classRoll.push(55); // ✅ Works fine
// // Alternatively, if you need an interface for some reason, extend Array<number>:


// interface Roll2 extends Array<number> {}

// let classRoll: Roll2 = [1, 2, 3, 4];
// classRoll.push(55); // ✅ Also works