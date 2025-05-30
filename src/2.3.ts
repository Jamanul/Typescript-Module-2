{
    // generic --> which i can use as many types
    type genericArray<T> = Array<T>
    
    const numberArray:genericArray<number> = [1,2,3]

    const booleanArray:genericArray<boolean> =[true,false]

    const stringArray:genericArray<string> =["a","b","c"]


    interface Object1 {
        name:string,
        age:number,
        role:string
    }

    const arrayOfObject:genericArray<Object1> =[{
        name:'mr.x',
        age:12,
        role:'senior'
    },
    {
        name:'mr.y',
        age:123,
        role:'junior'
    }
]

// toupel
type GenericTuple<X,Y> =[X,Y]


const arrayOfTuple:GenericTuple<number,boolean> =[1,true]



}