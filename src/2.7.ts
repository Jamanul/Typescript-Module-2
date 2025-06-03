{
    // keyof properties of constrain --> i think best usecase of keyof is getting the object.keys

    const useOfKeyof = <X,Y extends keyof X>(obj:X,key:Y)=>{
        return  console.log(obj[key])
    }

    interface User {
        name:string,
        role:string
    }


    const user:User ={
        name:'dipto boss',
        role:'boss'
    }

    const res = useOfKeyof(user,"role")



}